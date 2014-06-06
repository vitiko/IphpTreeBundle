<?php

namespace Iphp\TreeBundle\Model;

use Gedmo\Mapping\Annotation as Gedmo;


abstract class TreeNode implements TreeNodeInterface
{


    protected $id;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var integer $left
     */
    protected $left;

    /**
     * @var integer $right
     */
    protected $right;

    /**
     * @var integer $root
     */
    protected $root;

    /**
     * @var integer $level
     */
    protected $level;


    protected $parent;


    protected $children;

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $left
     */
    public function setLeft($left)
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param mixed $right
     */
    public function setRight($right)
    {
        $this->right = $right;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param mixed $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }



    public function setParent(TreeNodeInterface $parent = null)
    {
        $this->parent = $parent;
        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }


    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add children
     *
     * @param \Iphp\TreeBundle\Model\AbstractTree $children
     */
    public function addChildren(TreeNodeInterface $children)
    {
        $this->children[] = $children;
    }

}