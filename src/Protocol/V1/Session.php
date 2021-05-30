<?php

/*
 * This file is part of the GraphAware Bolt package.
 *
 * (c) GraphAware Ltd <christophe@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Bolt\Protocol\V1;

use Bolt\Bolt;
use Bolt\error\MessageException;
use Exception;
use GraphAware\Bolt\Driver;
use GraphAware\Bolt\Protocol\AbstractSession;
use GraphAware\Bolt\Protocol\Pipeline;
use GraphAware\Bolt\Exception\MessageFailureException;
use GraphAware\Bolt\Result\Result as CypherResult;
use GraphAware\Common\Collection\ArrayList;
use GraphAware\Common\Collection\Map;
use GraphAware\Common\Cypher\Statement;
use RuntimeException;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_pop;
use function preg_match;
use function substr;

class Session extends AbstractSession
{
    const PROTOCOL_VERSION = 1;

    /**
     * @var bool
     */
    public $isInitialized = false;

    /**
     * @var Transaction|null
     */
    public $transaction;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * @param Bolt $io
     * @param EventDispatcherInterface $dispatcher
     * @param array $credentials
     * @throws Exception
     */
    public function __construct(Bolt $io, EventDispatcherInterface $dispatcher, array $credentials)
    {
        parent::__construct($io, $dispatcher);

        $this->credentials = $credentials;
        $this->init();
    }

    public static function getProtocolVersion(): string
    {
        return self::PROTOCOL_VERSION;
    }

    /**
     * @throws Exception
     */
    public function run($statement, array $parameters = array(), $tag = null): CypherResult
    {
        foreach ($parameters as $i => $parameter) {
            if ($parameter instanceof ArrayList) {
                $parameters[$i] = array_values($parameter->getElements());
            } elseif ($parameter instanceof Map) {
                $object = new stdClass();
                foreach ($parameter->getElements() as $key => $value) {
                    $object->$key = $value;
                }
                $parameters[$i] = $object;
            }
        }
        try {
            $x = $this->io->run($statement, $parameters);
            $results = $this->io->pullAll();
        } catch (MessageException $e) {
            $exception = new MessageFailureException($e->getMessage(), $e->getCode(), $e);
            $message = $e->getMessage();
            preg_match('/\(Neo\.[\w\W]*\)$/', $message, $matches);
            $exception->setStatusCode(substr(substr($matches[0], 1), 0, -1));
            $this->io->rollback();
            $this->isInitialized = false;
            $this->init();
            throw $exception;
        }

        $cypherResult = new CypherResult(Statement::create($statement, $parameters, $tag));
        $cypherResult->setFields($x['fields'] ?? []);

        $meta = array_pop($results);
        foreach ($results as $result) {
            $cypherResult->pushRecord($result);
        }

        if (isset($meta['stats'])) {
            $cypherResult->setStatistics($meta['stats']);
        } else {
            $cypherResult->setStatistics([]);
        }

        return $cypherResult;
    }

    /**
     * {@inheritdoc}
     */
    public function runPipeline(Pipeline $pipeline)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createPipeline($query = null, array $parameters = [], $tag = null): Pipeline
    {
        return new Pipeline($this);
    }

    /**
     * @throws Exception
     */
    public function recv($statement, array $parameters = array(), $tag = null): CypherResult
    {
        return $this->run($statement, $parameters, $tag);
    }

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->io->init(Driver::getUserAgent(), $this->credentials['user'], $this->credentials['pass']);

        $this->isInitialized = true;
    }

    /**
     * @throws Exception
     */
    public function close()
    {
        $this->io->reset();
        $this->isInitialized = false;
    }

    public function transaction(): Transaction
    {
        if ($this->transaction instanceof Transaction) {
            throw new RuntimeException('A transaction is already bound to this session');
        }

        return new Transaction($this);
    }

    /**
     * @throws Exception
     */
    public function commit()
    {
        $this->io->commit();
    }

    /**
     * @throws Exception
     */
    public function rollback()
    {
        $this->io->rollback();
    }

    /**
     * @throws Exception
     */
    public function begin()
    {
        $this->io->begin();
    }
}
