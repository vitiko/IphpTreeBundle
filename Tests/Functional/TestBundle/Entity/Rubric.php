<?php

namespace Iphp\TreeBundle\Tests\Functional\TestBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name = "rubric")
 * @Gedmo\Tree(type="nested")
 */

class Rubric
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $title;






    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rgt;



    /**
     * @ORM\Column(name="path", type="string", length=64)
     */
    private $path;


    /**
     * @ORM\Column(name="fullPath", type="string", length=3000, nullable=true)
     */
    private $fullPath;




    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Rubric", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Rubric", mappedBy="parent")
     */
    private $children;


    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     * @return Photo
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setChildren($children)
    {
        $this->children = $children;
        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function setLft($lft)
    {
        $this->lft = $lft;
        return $this;
    }

    public function getLft()
    {
        return $this->lft;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
        return $this;
    }

    public function getRgt()
    {
        return $this->rgt;
    }

    public function setLvl($lvl)
    {
        $this->lvl = $lvl;
        return $this;
    }

    public function getLvl()
    {
        return $this->lvl;
    }

    public function setFullPath($fullPath)
    {
        $this->fullPath = $fullPath;
        return $this;
    }

    public function getFullPath()
    {
        return $this->fullPath;
    }


}
