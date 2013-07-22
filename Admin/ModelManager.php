<?php
namespace Iphp\TreeBundle\Admin;

use \Sonata\AdminBundle\Exception\ModelManagerException;

class ModelManager extends \Sonata\DoctrineORMAdminBundle\Model\ModelManager
{


    function changePosition($node, $parentId, $afterId)
    {
        $changeParent =

        $entityManager = $this->getEntityManager($node);
        $repository = $entityManager->getRepository(get_class($node));



        //print "change: ".$node->getId().", parent:".$parentId.", after: ".$afterId;


        if ($afterId != '0')
        {
            $afterNode = $this->find(get_class($node), $afterId);
            if (!$afterNode )   throw new ModelManagerException ('Node with id id='.$afterId.' not found');
            $repository->persistAsNextSiblingOf($node, $afterNode);
        }
        else
        {
             if ($node->getParentId() == $parentId)
             {
                 $repository->persistAsFirstChild($node);
             }
            else
            {
                $parentObj = $this->find(get_class($node), $parentId);
                if (!$parentObj ) throw new ModelManagerException ('Parent node with id id='.$parentId.' not found');

                $repository->persistAsFirstChildOf($node, $parentObj);
            }
        }
        $entityManager->flush();

    }


}
