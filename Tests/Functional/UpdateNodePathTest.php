<?php

namespace Iphp\TreeBundle\Tests\Functional;

/**
 * @author Vitiko <vitiko@mail.ru>
 */

use Iphp\TreeBundle\Tests\Functional\TestBundle\Entity\Rubric;


class UpdateNodePathTest extends BaseTestCase
{

    public function testUpdateNodePath()
    {


        $this->loadFixtures();


        $repo = $this->getEntityManager()->getRepository('TestBundle:Rubric');


        $rubrics = $repo->findBy(array(), array('lft' => 'ASC'));

        $secondLevelRubric = $repo->findOneBy(array('fullPath' => '/vg/cr/'));

        $this->assertSame($secondLevelRubric->getFullPath(), '/vg/cr/');
        $this->assertSame($secondLevelRubric->getPath(), 'cr');

        $secondLevelRubric->setPath('cr-new');


        $this->getEntityManager()->persist($secondLevelRubric);
        $this->getEntityManager()->flush();


        $this->assertSame($secondLevelRubric->getFullPath(), '/vg/cr-new/');




       $secondLevelRubricUpdated = $repo->findOneByTitle('Carrots');


       $this->assertSame($secondLevelRubricUpdated->getFullPath(), '/vg/cr-new/');
    }
}