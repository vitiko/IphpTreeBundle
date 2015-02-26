<?php
namespace Iphp\TreeBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Route\RouteCollection;
use Iphp\TreeBundle\Model\TreeNodeIterator;


class TreeAdmin extends Admin
{


    function getListTemplate()
    {
        $container = $this->getConfigurationPool()->getContainer();
        if ($container->hasParameter('expand_rubrics') and $container->getParameter('expand_rubrics') == true){
            return 'IphpTreeBundle:CRUD:treeCollapsible.html.twig';
        } else {
            return 'IphpTreeBundle:CRUD:tree.html.twig';
        }
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('chpos', '{moveNodeId}/chpos/{moveNodeParent}/{moveNodeAfter}',
            array('_controller' => 'IphpTreeBundle:CRUD:changePosition'));

    }


    public function changePosition($node, $parent, $after)
    {
        $this->preUpdate($node);
        $this->getModelManager()->changePosition($node, $parent, $after);
        $this->postUpdate($node);
    }


    public function getTreeIterator()
    {

        $rootes = $this->getRootes();

        if (!$rootes) $rootes = array($this->createRootItem());

        if (!$rootes) return null;
        if (sizeof($rootes) > 1) throw new \Exception ('more tham 1 root');

        $rootNodesQb = $this->getDatagrid()->getQuery()->getQueryBuilder()
            ->where('o.root = :root')->setParameters(array('root' => $rootes[0]))->orderBy('o.left');


        return new TreeNodeIterator($rootNodesQb->getQuery()->getResult());
    }


    protected function   getRootes()
    {
        $rootQb = $this->getDatagrid()->getQuery()->getQueryBuilder()->where('o.root = o.id');
        if ($this->isChild()) {


            $linksToParent = array_values($this->getModelManager()->getMetadata($this->getClass())
                ->getAssociationsByTargetClass($this->getParent()->getClass()));

            if (sizeof($linksToParent) > 1)
                throw new \Exception ('more than 1 link from ' . $this->getClass() . ' to ' . $this->getParent()->getClass());

            if ($linksToParent) $rootQb->where('o.' . $linksToParent[0]['fieldName'] . ' = :parent')
                ->setParameter('parent', $this->getParent()->getSubject());
        }

        $rootes = $rootQb->getQuery()->getResult();

        return $rootes;
    }

    /**
     * By default root node not create
     */
    protected function createRootItem()
    {
    }


    public function getCurrentRoot()
    {
        if ($this->getSubject() && $this->getSubject()->getId()) return $this->getSubject()->getRoot();

        if ($this->isChild()) {
             $rootes = $this->getRootes();

            return $rootes ? $rootes[0] : null;
        }

        return null;
    }


}