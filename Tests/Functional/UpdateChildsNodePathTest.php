<?php

namespace Iphp\TreeBundle\Tests\Functional;

/**
 * @author Vitiko <vitiko@mail.ru>
 */

use Iphp\TreeBundle\Tests\Functional\TestBundle\Entity\Rubric;


class UpdateChildsNodePathTest extends BaseTestCase
{

    public function testUpdateNodePath()
    {


        $this->loadFixtures();


        $repo = $this->getEntityManager()->getRepository('TestBundle:Rubric');


        $firstLevelRubric = $repo->findOneBy(array('fullPath' => '/vg/'));
        $this->assertSame($firstLevelRubric->getFullPath(), '/vg/');
        $this->assertSame($firstLevelRubric->getPath(), 'vg');


        $secondLevelRubric = $repo->findOneBy(array('fullPath' => '/vg/cr/'));
        $this->assertSame($secondLevelRubric->getFullPath(), '/vg/cr/');
        $this->assertSame($secondLevelRubric->getPath(), 'cr');

        $firstLevelRubric->setPath('vg-changed');


        $this->getEntityManager()->persist($secondLevelRubric);
        $this->getEntityManager()->flush();


        $this->assertSame($secondLevelRubric->getFullPath(), '/vg-changed/cr/');




       $secondLevelRubricUpdated = $repo->findOneByTitle('Carrots');


       $this->assertSame($secondLevelRubricUpdated->getFullPath(), '/vg-changed/cr/');
    }
}