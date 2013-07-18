<?php

namespace Iphp\TreeBundle\Tests\Functional;

/**
 * @author Vitiko <vitiko@mail.ru>
 */

use Iphp\TreeBundle\Tests\Functional\TestBundle\Entity\Rubric;


class CreateTreeTest extends BaseTestCase
{


    /**
     * test saving entity with file property from parent abstract uploadable class
     */
    public function testCreateTree()
    {
        $client = $this->createClient();
        $this->importDatabaseSchema();


        $food = new Rubric();
        $food->setTitle('Food')->setPath('');

        $fruits = new Rubric();
        $fruits->setTitle('Fruits')->setPath('fr');
        $fruits->setParent($food);

        $vegetables = new Rubric();
        $vegetables->setTitle('Vegetables')->setPath('vg');
        $vegetables->setParent($food);

        $carrots = new Rubric();
        $carrots->setTitle('Carrots')->setPath('cr');
        $carrots->setParent($vegetables);


        $this->getEntityManager()->persist($food);
        $this->getEntityManager()->persist($fruits);
        $this->getEntityManager()->persist($vegetables);
        $this->getEntityManager()->persist($carrots);
        $this->getEntityManager()->flush();

        $repo = $this->getEntityManager()->getRepository('TestBundle:Rubric');
        $rubrics = $repo->findAll();


        $defaultData = array(
            array('parent' => null, 'path' => '', 'fullPath' => '/', 'lft' => 1, 'rgt' => 8, 'lvl' => 0),
            array('path' => 'fr', 'fullPath' => '/fr/', 'lft' => 2, 'rgt' => 3, 'lvl' => 1),
            array('path' => 'vg', 'fullPath' => '/vg/', 'lft' => 4, 'rgt' => 7, 'lvl' => 1),
            array('path' => 'cr', 'fullPath' => '/vg/cr/', 'lft' => 5, 'rgt' => 6, 'lvl' => 2)

        );


        foreach ($rubrics as $pos => $rubric) {
            if (!isset($defaultData[$pos])) continue;

            foreach ($defaultData[$pos] as $property => $value) {
                $this->assertSame($rubric->{'get' . ucfirst($property)}(), $value);
            }


            if ($rubric->getFullPath() == '/vg/') $moveRubric = $rubric;
        }


        $repo->persistAsFirstChild($moveRubric);
        $this->getEntityManager()->flush();

        $repo = $this->getEntityManager()->getRepository('TestBundle:Rubric');
        $rubrics = $repo->findAll();


        $afterUpdateData = array(
            array('parent' => null, 'path' => '', 'fullPath' => '/', 'lft' => 1, 'rgt' => 8, 'lvl' => 0),
            array('path' => 'fr', 'fullPath' => '/fr/', 'lft' => 6, 'rgt' => 7, 'lvl' => 1),
            array('path' => 'vg', 'fullPath' => '/vg/', 'lft' => 2, 'rgt' => 5, 'lvl' => 1),
            array('path' => 'cr', 'fullPath' => '/vg/cr/', 'lft' => 3, 'rgt' => 4, 'lvl' => 2)

        );


        foreach ($rubrics as $pos => $rubric) {
            if (!isset($afterUpdateData[$pos])) continue;

            foreach ($afterUpdateData[$pos] as $property => $value) {
                $this->assertSame($rubric->{'get' . ucfirst($property)}(), $value);
            }

        }
    }

}