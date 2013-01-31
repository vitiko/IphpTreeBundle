<?php
namespace Iphp\TreeBundle\Tree\Strategy;


use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tree\Strategy;
use Doctrine\ORM\EntityManager;
use Gedmo\Tree\TreeListener;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Mapping\Event\AdapterInterface;

class Nested implements Strategy
{
    /*
* Previous sibling position
*/
    const PREV_SIBLING = 'PrevSibling';

    /**
     * Next sibling position
     */
    const NEXT_SIBLING = 'NextSibling';

    /**
     * Last child position
     */
    const LAST_CHILD = 'LastChild';

    /**
     * First child position
     */
    const FIRST_CHILD = 'FirstChild';

    /**
     * TreeListener
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * The max number of "right" field of the
     * tree in case few root nodes will be persisted
     * on one flush for node classes
     *
     * @var array
     */
    private $treeEdges = array();

    /**
     * Stores a list of node position strategies
     * for each node by object hash
     *
     * @var array
     */
    private $nodePositions = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(TreeListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Strategy::NESTED;
    }

    /**
     * get DQL expression for id value
     *
     * @param integer|string $id
     * @param EntityManager $em
     * @return string
     */
    private function getIdExpression($id, EntityManager $em)
    {
        if (is_string($id)) {
            $id = $em->getExpressionBuilder()->literal($id);
        }
        if ($id === null) {
            $id = 'NULL';
        }
        return (string)$id;
    }

    /**
     * Set node position strategy
     *
     * @param string $oid
     * @param string $position
     */
    public function setNodePosition($oid, $position)
    {
        $valid = array(
            self::FIRST_CHILD,
            self::LAST_CHILD,
            self::NEXT_SIBLING,
            self::PREV_SIBLING
        );
        if (!in_array($position, $valid, false)) {
            throw new \Gedmo\Exception\InvalidArgumentException("Position: {$position} is not valid in nested set tree");
        }
        $this->nodePositions[$oid] = $position;
    }

    /**
     * Operations on tree node insertion
     *
     * @param EntityManager $em
     * @param object $object - node
     * @param AdapterInterface $ea - event adapter
     * @return void
     */
    public function processScheduledInsertion( $em, $node, AdapterInterface $ea)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);

        $meta->getReflectionProperty($config['left'])->setValue($node, 0);
        $meta->getReflectionProperty($config['right'])->setValue($node, 0);
        if (isset($config['level'])) {
            $meta->getReflectionProperty($config['level'])->setValue($node, 0);
        }
        if (isset($config['root'])) {
            $meta->getReflectionProperty($config['root'])->setValue($node, 0);
        }


      //  print_r ($config);
       // exit();

        //Vitiko added

        $properties = $meta->getReflectionProperties();
        if (isset($properties['fullPath'])) $meta->getReflectionProperty('fullPath')->setValue($node, '/');


     //   $meta->

    }


    /**
     * Update path of node
     * @param object $node - target node
     * @param object $parent - destination node
     */

    protected function setPathData(QueryBuilder $qb, $node, $parent)
    {
        $nodeFullPath = $parent->getPropertyValue('fullPath') . $node->getPropertyValue('path') . '/';
        $qb->set('node.fullPath', $qb->expr()->literal($nodeFullPath));
    }



    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($em, $node, AdapterInterface $ea)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();

        $changeSet = $uow->getEntityChangeSet($node);
        if (isset($config['root']) && isset($changeSet[$config['root']])) {
            throw new \Gedmo\Exception\UnexpectedValueException("Root cannot be changed manualy, change parent instead");
        }

        $oid = spl_object_hash($node);
        if (isset($changeSet[$config['left']]) && isset($this->nodePositions[$oid])) {
            $wrapped = AbstractWrapper::wrap($node, $em);
            $parent = $wrapped->getPropertyValue($config['parent']);
            // revert simulated changeset
            $uow->clearEntityChangeSet($oid);
            $wrapped->setPropertyValue($config['left'], $changeSet[$config['left']][0]);
            $uow->setOriginalEntityProperty($oid, $config['left'], $changeSet[$config['left']][0]);
            // set back all other changes
            foreach ($changeSet as $field => $set) {
                if ($field !== $config['left']) {
                    $uow->setOriginalEntityProperty($oid, $field, $set[0]);
                    $wrapped->setPropertyValue($field, $set[1]);
                }
            }
            $uow->recomputeSingleEntityChangeSet($meta, $node);
            $this->updateNode($em, $node, $parent);
        } elseif (isset($changeSet[$config['parent']])) {
            $this->updateNode($em, $node, $changeSet[$config['parent']][1]);
        }

        // Vitiko: при обновлении рубрики если изменилмся ее путь но не изменились другие данные - обновить fullPath
        if (isset($changeSet['path']) && !(
                isset($changeSet[$config['left']]) || isset($changeSet[$config['parent']]))
        ) {
            $parent = (isset($changeSet[$config['parent']])) ? $changeSet[$config['parent']][1] : $node->getParent();
            $meta->getReflectionProperty('fullPath')->setValue($node, $parent->getFullPath() . $changeSet['path'][1] . '/');
        }
    }


    /**
       * {@inheritdoc}
       */
      public function processPostPersist($em, $node, AdapterInterface $ea)
      {
          $meta = $em->getClassMetadata(get_class($node));
          $config = $this->listener->getConfiguration($em, $meta->name);
          $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
          $this->updateNode($em, $node, $parent, self::LAST_CHILD);
      }

      /**
       * {@inheritdoc}
       */
      public function processScheduledDelete($em, $node)
      {
          $meta = $em->getClassMetadata(get_class($node));
          $config = $this->listener->getConfiguration($em, $meta->name);
          $uow = $em->getUnitOfWork();

          $wrapped = AbstractWrapper::wrap($node, $em);
          $leftValue = $wrapped->getPropertyValue($config['left']);
          $rightValue = $wrapped->getPropertyValue($config['right']);

          if (!$leftValue || !$rightValue) {
              return;
          }
          $rootId = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;
          $diff = $rightValue - $leftValue + 1;
          if ($diff > 2) {
              $dql = "SELECT node FROM {$config['useObjectClass']} node";
              $dql .= " WHERE node.{$config['left']} BETWEEN :left AND :right";
              if (isset($config['root'])) {
                  $dql .= " AND node.{$config['root']} = ".$this->getIdExpression($rootId, $em);
              }
              $q = $em->createQuery($dql);
              // get nodes for deletion
              $q->setParameter('left', $leftValue + 1);
              $q->setParameter('right', $rightValue - 1);
              $nodes = $q->getResult();
              foreach ((array)$nodes as $removalNode) {
                  $uow->scheduleForDelete($removalNode);
              }
          }
          $this->shiftRL($em, $config['useObjectClass'], $rightValue + 1, -$diff, $rootId);
      }

      /**
       * {@inheritdoc}
       */
      public function onFlushEnd($em, AdapterInterface $ea)
      {
          // reset values
          $this->treeEdges = array();
          $this->updatesOnNodeClasses = array();
      }

      /**
       * {@inheritdoc}
       */
      public function processPreRemove($em, $node)
      {}

      /**
       * {@inheritdoc}
       */
      public function processPrePersist($em, $node)
      {}

      /**
       * {@inheritdoc}
       */
      public function processPreUpdate($em, $node)
      {}

      /**
       * {@inheritdoc}
       */
      public function processMetadataLoad($em, $meta)
      {}

      /**
       * {@inheritdoc}
       */
      public function processPostUpdate($em, $entity, AdapterInterface $ea)
      {}

      /**
       * {@inheritdoc}
       */
      public function processPostRemove($em, $entity, AdapterInterface $ea)
      {}

    /**
     * Update the $node with a diferent $parent
     * destination
     *
     * @param EntityManager $em
     * @param object $node - target node
     * @param object $parent - destination node
     * @param string $position
     * @throws Gedmo\Exception\UnexpectedValueException
     * @return void
     */
    public function updateNode(EntityManager $em, $node, $parent, $position = 'FirstChild')
    {

        // die (1);
        $wrapped = AbstractWrapper::wrap($node, $em);
        $meta = $wrapped->getMetadata();
        $config = $this->listener->getConfiguration($em, $meta->name);


        $rootId = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;
        $identifierField = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();

        $left = $wrapped->getPropertyValue($config['left']);
        $right = $wrapped->getPropertyValue($config['right']);

        $isNewNode = empty($left) && empty($right);
        if ($isNewNode) {
            $left = 1;
            $right = 2;
        }

        //   var_dump($node);
        //    var_dump($isNewNode);


        $oid = spl_object_hash($node);
        if (isset($this->nodePositions[$oid])) {
            $position = $this->nodePositions[$oid];
        }

        //  print "\n".$position." ".$node->getTitle()."(".$oid.")";
        $level = 0;
        $treeSize = $right - $left + 1;
        $newRootId = null;
        if ($parent) {
            $wrappedParent = AbstractWrapper::wrap($parent, $em);

            $parentRootId = isset($config['root']) ? $wrappedParent->getPropertyValue($config['root']) : null;
            $parentLeft = $wrappedParent->getPropertyValue($config['left']);
            $parentRight = $wrappedParent->getPropertyValue($config['right']);
            if (!$isNewNode && $rootId === $parentRootId && $parentLeft >= $left && $parentRight <= $right) {
                throw new UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
            if (isset($config['level'])) {
                $level = $wrappedParent->getPropertyValue($config['level']);
            }


            switch ($position) {

                case self::PREV_SIBLING:
                    $newParent = $wrappedParent->getPropertyValue($config['parent']);
                    if (is_null($newParent) && (isset($config['root']) || $isNewNode)) {
                        throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                    }
                    $wrapped->setPropertyValue($config['parent'], $newParent);
                    $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                    $start = $parentLeft;
                    break;

                case self::NEXT_SIBLING:
                    $newParent = $wrappedParent->getPropertyValue($config['parent']);
                    if (is_null($newParent) && (isset($config['root']) || $isNewNode)) {
                        throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                    }
                    $wrapped->setPropertyValue($config['parent'], $newParent);
                    $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $node);
                    $start = $parentRight + 1;
                    break;

                case self::LAST_CHILD:
                    $start = $parentRight;
                    $level++;
                    break;

                case self::FIRST_CHILD:
                default:
                    $start = $parentLeft + 1;
                    $level++;
                    break;
            }
            $this->shiftRL($em, $config['useObjectClass'], $start, $treeSize, $parentRootId);
            if (!$isNewNode && $rootId === $parentRootId && $left >= $start) {
                $left += $treeSize;
                $wrapped->setPropertyValue($config['left'], $left);
            }
            if (!$isNewNode && $rootId === $parentRootId && $right >= $start) {
                $right += $treeSize;
                $wrapped->setPropertyValue($config['right'], $right);
            }
            $newRootId = $parentRootId;
        } elseif (!isset($config['root'])) {
            $start = isset($this->treeEdges[$meta->name]) ?
                    $this->treeEdges[$meta->name] : $this->max($em, $config['useObjectClass']);
            $this->treeEdges[$meta->name] = $start + 2;
            $start++;
        } else {
            $start = 1;
            $newRootId = $nodeId;
        }


        // die ('Update Node');

        $diff = $start - $left;
        if (!$isNewNode) {
            $levelDiff = isset($config['level']) ? $level - $wrapped->getPropertyValue($config['level']) : null;
            $this->shiftRangeRL(
                $em,
                $config['useObjectClass'],
                $left,
                $right,
                $diff,
                $rootId,
                $newRootId,
                $levelDiff
            );
            $this->shiftRL($em, $config['useObjectClass'], $left, -$treeSize, $rootId);

        } else {


            $qb = $em->createQueryBuilder();
            $qb->update($config['useObjectClass'], 'node');
            if (isset($config['root'])) {
                $qb->set('node.' . $config['root'], $newRootId);
                $wrapped->setPropertyValue($config['root'], $newRootId);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['root'], $newRootId);
            }
            if (isset($config['level'])) {
                $qb->set('node.' . $config['level'], $level);
                $wrapped->setPropertyValue($config['level'], $level);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['level'], $level);
            }
            if (isset($newParent)) {
                $wrappedNewParent = AbstractWrapper::wrap($newParent, $em);
                $newParentId = $wrappedNewParent->getIdentifier();
                $qb->set('node.' . $config['parent'], $newParentId);
                $wrapped->setPropertyValue($config['parent'], $newParent);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['parent'], $newParent);
            }


            $properties = $meta->getReflectionProperties();
            if (isset($properties['fullPath']) && isset($wrappedParent))
                $this->setPathData($qb, $wrapped, isset($newParent) ? $newParent : $wrappedParent);

            $qb->set('node.' . $config['left'], $left + $diff);
            $qb->set('node.' . $config['right'], $right + $diff);
            $qb->where("node.{$identifierField} = {$nodeId}");

            //var_dump ($qb->getDQL());
            //exit();
            $qb->getQuery()->getSingleScalarResult();
            $wrapped->setPropertyValue($config['left'], $left + $diff);
            $wrapped->setPropertyValue($config['right'], $right + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['left'], $left + $diff);
            $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['right'], $right + $diff);
        }
    }




    /**
         * Get the edge of tree
         *
         * @param EntityManager $em
         * @param string $class
         * @param integer $rootId
         * @return integer
         */
        public function max(EntityManager $em, $class, $rootId = 0)
        {
            $meta = $em->getClassMetadata($class);
            $config = $this->listener->getConfiguration($em, $meta->name);

            $dql = "SELECT MAX(node.{$config['right']}) FROM {$config['useObjectClass']} node";
            if (isset($config['root']) && $rootId) {
                $dql .= " WHERE node.{$config['root']} = ".$this->getIdExpression($rootId, $em);
            }

            $query = $em->createQuery($dql);
            $right = $query->getSingleScalarResult();
            return intval($right);
        }

        /**
         * Shift tree left and right values by delta
         *
         * @param EntityManager $em
         * @param string $class
         * @param integer $first
         * @param integer $delta
         * @param integer|string $rootId
         * @return void
         */
        public function shiftRL(EntityManager $em, $class, $first, $delta, $rootId = null)
        {
            $meta = $em->getClassMetadata($class);
            $config = $this->listener->getConfiguration($em, $class);

            $sign = ($delta >= 0) ? ' + ' : ' - ';
            $absDelta = abs($delta);

            $dql = "UPDATE {$meta->name} node";
            $dql .= " SET node.{$config['left']} = node.{$config['left']} {$sign} {$absDelta}";
            $dql .= " WHERE node.{$config['left']} >= {$first}";
            if (isset($config['root'])) {
                $dql .= " AND node.{$config['root']} = ".$this->getIdExpression($rootId, $em);
            }
            $q = $em->createQuery($dql);
            $q->getSingleScalarResult();

            $dql = "UPDATE {$meta->name} node";
            $dql .= " SET node.{$config['right']} = node.{$config['right']} {$sign} {$absDelta}";
            $dql .= " WHERE node.{$config['right']} >= {$first}";
            if (isset($config['root'])) {
                $dql .= " AND node.{$config['root']} = ".$this->getIdExpression($rootId, $em);
            }
            $q = $em->createQuery($dql);
            $q->getSingleScalarResult();
            // update in memory nodes increases performance, saves some IO
            foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
                // for inheritance mapped classes, only root is always in the identity map
                if ($className !== $meta->rootEntityName) {
                    continue;
                }
                foreach ($nodes as $node) {
                    if ($node instanceof Proxy && !$node->__isInitialized__) {
                        continue;
                    }
                    $oid = spl_object_hash($node);
                    $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                    $root = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
                    if ($root === $rootId && $left >= $first) {
                        $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                        $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['left'], $left + $delta);
                    }
                    $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                    if ($root === $rootId && $right >= $first) {
                        $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                        $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['right'], $right + $delta);
                    }
                }
            }
        }

        /**
         * Shift range of right and left values on tree
         * depending on tree level diference also
         *
         * @param EntityManager $em
         * @param string $class
         * @param integer $first
         * @param integer $last
         * @param integer $delta
         * @param integer|string $rootId
         * @param integer|string $destRootId
         * @param integer $levelDelta
         * @return void
         */
        public function shiftRangeRL(EntityManager $em, $class, $first, $last, $delta, $rootId = null, $destRootId = null, $levelDelta = null)
        {
            $meta = $em->getClassMetadata($class);
            $config = $this->listener->getConfiguration($em, $class);

            $sign = ($delta >= 0) ? ' + ' : ' - ';
            $absDelta = abs($delta);
            $levelSign = ($levelDelta >= 0) ? ' + ' : ' - ';
            $absLevelDelta = abs($levelDelta);

            $dql = "UPDATE {$meta->name} node";
            $dql .= " SET node.{$config['left']} = node.{$config['left']} {$sign} {$absDelta}";
            $dql .= ", node.{$config['right']} = node.{$config['right']} {$sign} {$absDelta}";
            if (isset($config['root'])) {
                $dql .= ", node.{$config['root']} = ".$this->getIdExpression($destRootId, $em);
            }
            if (isset($config['level'])) {
                $dql .= ", node.{$config['level']} = node.{$config['level']} {$levelSign} {$absLevelDelta}";
            }
            $dql .= " WHERE node.{$config['left']} >= {$first}";
            $dql .= " AND node.{$config['right']} <= {$last}";
            if (isset($config['root'])) {
                $dql .= " AND node.{$config['root']} = ".$this->getIdExpression($rootId, $em);
            }
            $q = $em->createQuery($dql);
            $q->getSingleScalarResult();
            // update in memory nodes increases performance, saves some IO
            foreach ($em->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
                // for inheritance mapped classes, only root is always in the identity map
                if ($className !== $meta->rootEntityName) {
                    continue;
                }
                foreach ($nodes as $node) {
                    if ($node instanceof Proxy && !$node->__isInitialized__) {
                        continue;
                    }
                    $left = $meta->getReflectionProperty($config['left'])->getValue($node);
                    $right = $meta->getReflectionProperty($config['right'])->getValue($node);
                    $root = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($node) : null;
                    if ($root === $rootId && $left >= $first && $right <= $last) {
                        $oid = spl_object_hash($node);
                        $uow = $em->getUnitOfWork();

                        $meta->getReflectionProperty($config['left'])->setValue($node, $left + $delta);
                        $uow->setOriginalEntityProperty($oid, $config['left'], $left + $delta);
                        $meta->getReflectionProperty($config['right'])->setValue($node, $right + $delta);
                        $uow->setOriginalEntityProperty($oid, $config['right'], $right + $delta);
                        if (isset($config['root'])) {
                            $meta->getReflectionProperty($config['root'])->setValue($node, $destRootId);
                            $uow->setOriginalEntityProperty($oid, $config['root'], $destRootId);
                        }
                        if (isset($config['level'])) {
                            $level = $meta->getReflectionProperty($config['level'])->getValue($node);
                            $meta->getReflectionProperty($config['level'])->setValue($node, $level + $levelDelta);
                            $uow->setOriginalEntityProperty($oid, $config['level'], $level + $levelDelta);
                        }
                    }
                }
            }
        }

}