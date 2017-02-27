<?php

namespace GraphAware\Bolt\Tests\Integration;
use GraphAware\Common\Cypher\Statement;
use GraphAware\Common\Result\StatementStatisticsInterface;

/**
 * Class RealLifeUseCasesITest
 * @package GraphAware\Bolt\Tests\Integration
 *
 * @group real-life
 */
class RealLifeUseCasesITest extends IntegrationTestCase
{
    public function testNestedEmptyArrays()
    {
        $this->emptyDB();
        $batches = [];
        for ($i = 1; $i < 10; ++$i) {
            $batches[] = $this->createBatch($i);
        }

        $query = 'UNWIND {batches} as batch
        MERGE (p:Person {id: batch.id})
        SET p += batch.props
        WITH p, batch
        UNWIND batch.prev as prev
        MERGE (o:Person {id: prev})
        MERGE (p)-[:KNOWS]->(o)';

        $session = $this->driver->session();
        $session->run($query, ['batches' => $batches]);
    }

    /**
     * @group stats
     */
    public function testResultSummaryReturnsStats()
    {
        $this->emptyDB();
        $session = $this->driver->session();
        $result = $session->run('CREATE (n)');
        $this->assertInstanceOf(StatementStatisticsInterface::class, $result->summarize()->updateStatistics());

        $tx = $session->transaction();
        $tx->begin();
        $result = $tx->run(Statement::create('CREATE (n)'));
        $tx->commit();
        $this->assertInstanceOf(StatementStatisticsInterface::class, $result->summarize()->updateStatistics());
    }

    private function createBatch($i)
    {
        $batch = [
            'id' => $i,
            'props' => [
                'login' => sprintf('login%d', $i)
            ],
            'prev' => $i > 0 ? range(1, 10) : []
        ];

        return $batch;
    }
}