<?php

/*
 * This file is part of the GraphAware Bolt package.
 *
 * (c) Graph Aware Limited <http://graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Bolt\Result;

use GraphAware\Bolt\PackStream\Structure\Structure;
use GraphAware\Bolt\Record\RecordView;
use GraphAware\Bolt\Result\Type\Node;
use GraphAware\Bolt\Result\Type\Path;
use GraphAware\Bolt\Result\Type\Relationship;
use GraphAware\Bolt\Result\Type\UnboundRelationship;
use GraphAware\Common\Cypher\StatementInterface;
use GraphAware\Common\Result\AbstractRecordCursor;
use GraphAware\Common\Result\Record;
use ReflectionClass;
use RuntimeException;

class Result extends AbstractRecordCursor
{
    /**
     * @var RecordView[]
     */
    protected $records = [];

    /**
     * @var array
     */
    protected $fields;

    /**
     * {@inheritdoc}
     */
    public function __construct(StatementInterface $statement)
    {
        $this->resultSummary = new ResultSummary($statement);

        parent::__construct($statement);
    }

    public function pushRecord(array $elements)
    {
        $elts = $this->array_map_deep($elements);
        $this->records[] = new RecordView($this->fields, $elts);
    }

    /**
     * @return RecordView[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @return RecordView
     *
     * @throws \RuntimeException When there is no record.
     */
    public function getRecord()
    {
        if (count($this->records) < 1) {
            throw new \RuntimeException('There is no record');
        }

        return $this->records[0];
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param array $stats
     */
    public function setStatistics(array $stats)
    {
        $this->resultSummary->setStatistics($stats);
    }

    /**
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return ResultSummary
     */
    public function summarize()
    {
        return $this->resultSummary;
    }

    public function position()
    {
        // TODO: Implement position() method.
    }

    public function skip()
    {
        // TODO: Implement skip() method.
    }

    private function array_map_deep(array $array)
    {
        foreach ($array as $k => $v) {
            if ($v instanceof \Bolt\structures\Node) {
                $array[$k] = new Node($v->id(), $v->labels(), $v->properties());
            } elseif ($v instanceof \Bolt\structures\Relationship) {
                $array[$k] = new Relationship($v->id(), $v->startNodeId(), $v->endNodeId(), $v->type(), $v->properties());
            } elseif ($v instanceof \Bolt\structures\UnboundRelationship) {
                $array[$k] = new UnboundRelationship($v->id(), $v->type(), $v->properties());
            } elseif ($v instanceof \Bolt\structures\Path) {
                $array[$k] = new Path($this->array_map_deep($v->nodes()), $this->array_map_deep($v->rels()), $this->array_map_deep($v->ids()));
            } elseif ($v instanceof Structure) {
                $array[$k] = $this->array_map_deep($v->getElements());
            } elseif (is_array($v)) {
                $array[$k] = $this->array_map_deep($v);
            }
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return count($this->records);
    }

    /**
     * @return RecordView
     * @throws \RuntimeException When there is no record
     */
    public function firstRecord()
    {
        if (!empty($this->records)) {
            return $this->records[0];
        }

        throw new RuntimeException('There is no record');
    }

    /**
     * {@inheritdoc}
     */
    public function firstRecordOrDefault($default)
    {
        if (0 === $this->size()) {
            return $default;
        }

        return $this->firstRecord();
    }


}
