<?php

namespace Iphp\TreeBundle\Tree\Strategy;

use Gedmo\Exception\UnexpectedValueException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Proxy\Proxy;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tree\Strategy;
use Doctrine\ORM\EntityManager;
use Gedmo\Tree\TreeListener;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tree\Strategy\ORM\Nested as NestedBase;


class Nested extends NestedBase implements Strategy
{

    protected function isUseMaterializedPath($config)
    {
        return isset($config['path']) && $config['path'] &&
            isset($config['path_source']) && $config['path_source'];
    }

    public function processScheduledInsertion($em, $node, AdapterInterface $ea)
    {
        parent::processScheduledInsertion($em, $node, $ea);

        $meta = $em->getClassMetadata(get_class($node));
        $properties = $meta->getReflectionProperties();
        $config = $this->listener->getConfiguration($em, $meta->name);


        if ($this->isUseMaterializedPath($config) &&
            isset($config['path_starts_with_separator']) &&
            $config['path_starts_with_separator'] &&
            isset($properties[$config['path']])
        )
            $meta->getReflectionProperty($config['path'])->setValue($node, $config['path_separator']);
    }


    public function processScheduledUpdate($em, $node, AdapterInterface $ea)
    {
        parent::processScheduledUpdate($em, $node, $ea);

        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($node);


        // Vitiko: при обновлении рубрики если изменилмся ее путь но не изменились другие данные - обновить fullPath
        if ($this->isUseMaterializedPath($config) &&
            isset($changeSet[$config['path_source']]) && !(
            isset($changeSet[$config['left']]) || isset($changeSet[$config['parent']]))
        ) {


            $parent = (isset($changeSet[$config['parent']])) ? $changeSet[$config['parent']][1] : $node->getParent();
            $wrappedParent = AbstractWrapper::wrap($parent, $em);

            $meta->getReflectionProperty($config['path'])
                ->setValue($node,
                $wrappedParent->getPropertyValue($config['path']) .
                    $changeSet[$config['path_source']][1] .
                    ($config['path_ends_with_separator'] ? $config['path_separator'] : ''));
        }


        if ($this->isUseMaterializedPath($config) && isset($changeSet[$config['parent']])) {
            $this->updateChildrenPath($em, $node);
        }
    }

    public function updateNode(EntityManager $em, $node, $parent, $position = 'FirstChild')
    {
        parent::updateNode($em, $node, $parent, $position);

        $this->updateNodePath($em, $node, $parent);
    }


    function updateNodePath($em, $node, $parent)
    {
        if (!$parent) return;
        $wrapped = AbstractWrapper::wrap($node, $em);
        $meta = $wrapped->getMetadata();

        $config = $this->listener->getConfiguration($em, $meta->name);

        if (!$this->isUseMaterializedPath($config)) return;
        $wrappedParent = AbstractWrapper::wrap($parent, $em);

        //this nodes in delayed
        $parentLeft = $wrappedParent->getPropertyValue($config['left']);
        $parentRight = $wrappedParent->getPropertyValue($config['right']);
        if (empty($parentLeft) && empty($parentRight)) return;


        $nodeFullPath = $wrappedParent->getPropertyValue($config['path']) .
            $wrapped->getPropertyValue($config['path_source']) .
            ($config['path_ends_with_separator'] ? $config['path_separator'] : '');


        $identifierField = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();
        $qb = $em->createQueryBuilder();
        $qb->update($config['useObjectClass'], 'node');
        $qb->set('node.' . $config['path'], $qb->expr()->literal($nodeFullPath));
        // node id cannot be null
        $qb->where($qb->expr()->eq('node.' . $identifierField, is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId));
        $qb->getQuery()->getSingleScalarResult();

        $oid = spl_object_hash($node);
        $wrapped->setPropertyValue($config['path'], $nodeFullPath);
        $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['path'], $nodeFullPath);

    }


    function updateChildrenPath($em, $node)
    {
        $wrapped = AbstractWrapper::wrap($node, $em);
        $meta = $wrapped->getMetadata();
        $config = $this->listener->getConfiguration($em, $meta->name);


        foreach ($em->getRepository($config['useObjectClass'])->children($node, false, $config['left']) as $child) {
            $this->updateNodePath($em, $child, $child->getParent());

        }
    }

}
