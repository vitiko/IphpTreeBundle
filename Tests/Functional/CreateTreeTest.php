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
        $fruits->setTitle('Fruits')->setPath('fr')->setParent($food);

        $vegetables = new Rubric();
        $vegetables->setTitle('Vegetables')->setPath('vg')->setParent($food);

        $carrots = new Rubric();
        $carrots->setTitle('Carrots')->setPath('cr')->setParent($vegetables);


        $meat = new Rubric();
        $meat->setTitle('Meat')->setPath('mt')->setParent($food);


        $this->getEntityManager()->persist($food);
        $this->getEntityManager()->persist($fruits);
        $this->getEntityManager()->persist($vegetables);
        $this->getEntityManager()->persist($carrots);
        $this->getEntityManager()->persist($meat);
        $this->getEntityManager()->flush();

        $repo = $this->getEntityManager()->getRepository('TestBundle:Rubric');
        $rubrics = $repo->findBy(  array(), array('lft' => 'ASC')  );


        $defaultData = array(
            array('parent' => null, 'path' => '', 'fullPath' => '/', 'lft' => 1, 'rgt' => 10, 'lvl' => 0),
            array('path' => 'fr', 'fullPath' => '/fr/', 'lft' => 2, 'rgt' => 3, 'lvl' => 1),
            array('path' => 'vg', 'fullPath' => '/vg/', 'lft' => 4, 'rgt' => 7, 'lvl' => 1),
            array('path' => 'cr', 'fullPath' => '/vg/cr/', 'lft' => 5, 'rgt' => 6, 'lvl' => 2),
            array('path' => 'mt', 'fullPath' => '/mt/', 'lft' => 8, 'rgt' => 9, 'lvl' => 1)
        );





        foreach ($rubrics as $pos => $rubric) {


            //print "\n". (str_repeat('   ', $rubric->getLvl()).$rubric->getFullPath());
            if (!isset($defaultData[$pos])) continue;

            foreach ($defaultData[$pos] as $property => $value) {
                $this->assertSame($rubric->{'get' . ucfirst($property)}(), $value);
            }


            if ($rubric->getFullPath() == '/vg/') $moveRubric = $rubric;
        }


        $repo->persistAsFirstChild($moveRubric);
        $this->getEntityManager()->flush();


        $this->getEntityManager()->clear();

        $rubrics = $repo->findBy(  array(), array('lft' => 'ASC')  );


        $afterUpdateData = array(
            array('parent' => null, 'path' => '', 'fullPath' => '/', 'lft' => 1, 'rgt' => 10, 'lvl' => 0),
            array('path' => 'vg', 'fullPath' => '/vg/', 'lft' => 2, 'rgt' => 5, 'lvl' => 1),
            array('path' => 'cr', 'fullPath' => '/vg/cr/', 'lft' => 3, 'rgt' => 4, 'lvl' => 2),
            array('path' => 'fr', 'fullPath' => '/fr/', 'lft' => 6, 'rgt' => 7, 'lvl' => 1),
            array('path' => 'mt', 'fullPath' => '/mt/', 'lft' => 8, 'rgt' => 9, 'lvl' => 1)
        );



        //print "\n\n";
        $moveRubric = $parentRubric = null;
        foreach ($rubrics as $pos => $rubric) {

           // print "\n". (str_repeat('   ', $rubric->getLvl()).$rubric->getFullPath());
            if (!isset($afterUpdateData[$pos])) continue;

            foreach ($afterUpdateData[$pos] as $property => $value) {
                $this->assertSame($rubric->{'get' . ucfirst($property)}(), $value);
            }


            if ($rubric->getFullPath() == '/vg/') $moveRubric = $rubric;
            if ($rubric->getFullPath() == '/fr/') $parentRubric = $rubric;
        }


        //Change parent - multiple rubric
        $repo->persistAsFirstChildOf($moveRubric, $parentRubric);
        $this->getEntityManager()->flush();

        $afterUpdateParentData = array(
            array('parent' => null, 'path' => '', 'fullPath' => '/', 'lft' => 1, 'rgt' => 10, 'lvl' => 0),
            array('path' => 'fr', 'fullPath' => '/fr/', 'lft' => 2, 'rgt' => 7, 'lvl' => 1),
            array('path' => 'vg', 'fullPath' => '/fr/vg/', 'lft' => 3, 'rgt' => 6, 'lvl' => 2),
            array('path' => 'cr', 'fullPath' => '/fr/vg/cr/', 'lft' => 4, 'rgt' => 5, 'lvl' => 3),
            array('path' => 'mt', 'fullPath' => '/mt/', 'lft' => 8, 'rgt' => 9, 'lvl' => 1)

        );

        $rubrics = $repo->findBy(  array(), array('lft' => 'ASC')  );

        foreach ($rubrics as $pos => $rubric) {

           // print "\n". (str_repeat('   ', $rubric->getLvl()).$rubric->getFullPath());

            if (!isset($afterUpdateParentData[$pos])) continue;

            foreach ($afterUpdateParentData[$pos] as $property => $value) {
                $this->assertSame($rubric->{'get' . ucfirst($property)}(), $value);
            }



            if ($rubric->getFullPath() == '/mt/') $moveRubric = $rubric;
            if ($rubric->getFullPath() == '/fr/') $parentRubric = $rubric;

        }


        //Change parent - single rubric
        $repo->persistAsFirstChildOf($moveRubric, $parentRubric);
        $this->getEntityManager()->flush();

        $afterUpdateParent2Data = array(
            array('parent' => null, 'path' => '', 'fullPath' => '/', 'lft' => 1, 'rgt' => 10, 'lvl' => 0),
            array('path' => 'fr', 'fullPath' => '/fr/', 'lft' => 2, 'rgt' => 9, 'lvl' => 1),
            array('path' => 'mt', 'fullPath' => '/fr/mt/', 'lft' => 3, 'rgt' => 4, 'lvl' => 2),
            array('path' => 'vg', 'fullPath' => '/fr/vg/', 'lft' => 5, 'rgt' => 8, 'lvl' => 2),
            array('path' => 'cr', 'fullPath' => '/fr/vg/cr/', 'lft' => 6, 'rgt' => 7, 'lvl' => 3)


        );

        $rubrics = $repo->findBy(  array(), array('lft' => 'ASC')  );

        foreach ($rubrics as $pos => $rubric) {

            //print "\n". (str_repeat('   ', $rubric->getLvl()).$rubric->getFullPath());

            if (!isset($afterUpdateParent2Data[$pos])) continue;

            foreach ($afterUpdateParent2Data[$pos] as $property => $value) {
                $this->assertSame($rubric->{'get' . ucfirst($property)}(), $value);
            }


            if ($rubric->getFullPath() == '/fr/vg/cr/') $moveRubric = $rubric;
            if ($rubric->getFullPath() == '/fr/') $siblingRubric = $rubric;


        }

        //Back to first level
        $repo->persistAsNextSiblingOf($moveRubric, $siblingRubric);
        $this->getEntityManager()->flush();


        $afterUpdateSiblingData = array(
            array('parent' => null, 'path' => '', 'fullPath' => '/', 'lft' => 1, 'rgt' => 10, 'lvl' => 0),
            array('path' => 'fr', 'fullPath' => '/fr/', 'lft' => 2, 'rgt' => 7, 'lvl' => 1),
            array('path' => 'mt', 'fullPath' => '/fr/mt/', 'lft' => 3, 'rgt' => 4, 'lvl' => 2),
            array('path' => 'vg', 'fullPath' => '/fr/vg/', 'lft' => 5, 'rgt' => 6, 'lvl' => 2),
            array('path' => 'cr', 'fullPath' => '/cr/', 'lft' => 8, 'rgt' => 9, 'lvl' => 1)
        );


        $rubrics = $repo->findBy(  array(), array('lft' => 'ASC')  );

        foreach ($rubrics as $pos => $rubric) {

           // print "\n". (str_repeat('   ', $rubric->getLvl()).$rubric->getFullPath());

            if (!isset($afterUpdateSiblingData[$pos])) continue;

            foreach ($afterUpdateSiblingData[$pos] as $property => $value) {
                $this->assertSame($rubric->{'get' . ucfirst($property)}(), $value);
            }



        }

        //  print_r ($arrayTree);
    }

}