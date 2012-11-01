<?php

namespace Iphp\TreeBundle\Model;


class  TreeNodeIterator implements \Iterator
{
    protected $nodes = array();
    protected $position = 0;

    /**
     * Текущий уровень нод в итераторе
     * @integer $level
     */
    protected $level = null;

    protected $options = array(
        'nodeClass' => '\\Iphp\\TreeBundle\Model\\TreeNodeWrapper',
        'onCreate' => null
    );

    function __construct($nodes = null, $options = array())
    {
        $this->options = array_merge($this->options, $options);

        $this->nodes = $nodes ? $this->prepareNodes($nodes) : array();
    }

    protected function prepareNodes($nodes)
    {
        $nodeClass = $this->options['nodeClass'];
        $onCreateFunc =  $this->options['onCreate'];

        $nodeByLevel = array();
        foreach ($nodes as $node) {
            $wrappedNode = new $nodeClass ($node);

            if ($onCreateFunc) $onCreateFunc($wrappedNode);

            $nodeByLevel[$node->getLevel()][$node->getId()] = $wrappedNode;
        }

        $levels = array_keys($nodeByLevel);
        sort($levels);
        $this->level = $levels[0];
        if (sizeof($levels) == 1) return array_values($nodeByLevel[$this->level]);


        foreach ($nodeByLevel as $level => $nodesById) {
            foreach ($nodesById as $nodeId => $node) {
                $parentLevel = $node->getLevel() - 1;
                $parentId = $node->getParentId();

                if (isset($nodeByLevel[$parentLevel][$parentId])) $nodeByLevel[$parentLevel][$parentId]->addChild($node);
            }
        }

        return array_values($nodeByLevel[$this->level]);
    }

    function rewind()
    {
        $this->position = 0;
    }

    function current()
    {
        return $this->nodes[$this->position];
    }

    function key()
    {
        return $this->position;
    }

    function next()
    {
        ++$this->position;
    }

    function valid()
    {
        return isset($this->nodes[$this->position]);
    }


    function count()
    {
        return sizeof($this->nodes);
    }

    function getLevel()
    {
        return $this->level;
    }
}