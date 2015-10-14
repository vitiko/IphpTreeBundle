<?php

namespace Iphp\TreeBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Sonata\AdminBundle\Controller\CRUDController as SonataCRUDController;

class CRUDController extends SonataCRUDController
{

    public function changePositionAction($moveNodeId, $moveNodeParent, $moveNodeAfter)
    {

        $node = $this->admin->getObject($moveNodeId);

        if ($moveNodeParent == 0) $moveNodeParent = 1;


        try {
            $this->admin->changePosition($node, $moveNodeParent, $moveNodeAfter);

            $result = true;
            $message = 'ОК';
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $result = false;
        }
        $response = new Response(json_encode(
            array('id' => $moveNodeId,
                'parent' => $moveNodeParent,
                'after' => $moveNodeAfter,
                'result' => $result,
                'message' => $message)));
        $response->headers->set('Content-Type', 'application/json');


        return $response;
    }


    public function listAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        //  $datagrid = $this->admin->getDatagrid();
        //  $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        //   $this->get('twig')->getExtension('form')->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getListTemplate(), array(
            'action' => 'list',
            'treeIterator' => $this->admin->getTreeIterator()
            // 'form' => $formView,
            //'datagrid' => $datagrid
        ));
    }


}
