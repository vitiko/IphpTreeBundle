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


    public function processScheduledInsertion($em, $node, AdapterInterface $ea)
    {
        parent::processScheduledInsertion($em, $node, $ea);

        $meta = $em->getClassMetadata(get_class($node));
        $properties = $meta->getReflectionProperties();
        if (isset($properties['fullPath'])) $meta->getReflectionProperty('fullPath')->setValue($node, '/');
    }



    public function processScheduledUpdate($em, $node, AdapterInterface $ea)
    {
        parent::processScheduledUpdate($em, $node, $ea);

        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();

        $changeSet = $uow->getEntityChangeSet($node);


        // Vitiko: при обновлении рубрики если изменилмся ее путь но не изменились другие данные - обновить fullPath
        if (isset($changeSet['path']) && !(
            isset($changeSet[$config['left']]) || isset($changeSet[$config['parent']]))
        ) {
            $parent = (isset($changeSet[$config['parent']])) ? $changeSet[$config['parent']][1] : $node->getParent();
            $meta->getReflectionProperty('fullPath')->setValue($node, $parent->getFullPath() . $changeSet['path'][1] . '/');
        }
    }

    public function updateNode(EntityManager $em, $node, $parent, $position = 'FirstChild')
    {
        parent::updateNode($em, $node, $parent, $position);

        $wrapped = AbstractWrapper::wrap($node, $em);
        $meta = $wrapped->getMetadata();
        $properties = $meta->getReflectionProperties();

        if ($parent && isset($properties['fullPath'])) {
            $config = $this->listener->getConfiguration($em, $meta->name);
            $wrappedParent = AbstractWrapper::wrap($parent, $em);
            $identifierField = $meta->getSingleIdentifierFieldName();
            $nodeId = $wrapped->getIdentifier();
            $oid = spl_object_hash($node);

            $nodeFullPath = $wrappedParent->getPropertyValue('fullPath') . $wrapped->getPropertyValue('path') . '/';


            $qb = $em->createQueryBuilder();
            $qb->update($config['useObjectClass'], 'node');
            $qb->set('node.fullPath', $qb->expr()->literal($nodeFullPath));
            // node id cannot be null
            $qb->where($qb->expr()->eq('node.'.$identifierField, is_string($nodeId) ? $qb->expr()->literal($nodeId) : $nodeId));
            $qb->getQuery()->getSingleScalarResult();

            $wrapped->setPropertyValue('fullPath', $nodeFullPath );
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, 'fullPath', $nodeFullPath);
        }

    }


}
