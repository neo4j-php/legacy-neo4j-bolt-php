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

use BadMethodCallException;
use Bolt\Bolt;
use Bolt\error\MessageException;
use Exception;
use GraphAware\Bolt\Driver;
use GraphAware\Bolt\Exception\BoltInvalidArgumentException;
use GraphAware\Bolt\IO\AbstractIO;
use GraphAware\Bolt\Protocol\AbstractSession;
use GraphAware\Bolt\Protocol\Message\AbstractMessage;
use GraphAware\Bolt\Protocol\Message\AckFailureMessage;
use GraphAware\Bolt\Protocol\Message\InitMessage;
use GraphAware\Bolt\Protocol\Message\PullAllMessage;
use GraphAware\Bolt\Protocol\Message\RawMessage;
use GraphAware\Bolt\Protocol\Message\RunMessage;
use GraphAware\Bolt\Protocol\Pipeline;
use GraphAware\Bolt\Exception\MessageFailureException;
use GraphAware\Bolt\Result\Result as CypherResult;
use GraphAware\Common\Collection\ArrayList;
use GraphAware\Common\Collection\Map;
use GraphAware\Common\Cypher\Statement;
use Laudis\Neo4j\Network\Bolt\BoltDriver;
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
     */
    public function __construct(Bolt $io, EventDispatcherInterface $dispatcher, array $credentials)
    {
        parent::__construct($io, $dispatcher);

        $this->credentials = $credentials;
        $this->init();
    }

    /**
     * {@inheritdoc}
     */
    public static function getProtocolVersion()
    {
        return self::PROTOCOL_VERSION;
    }

    /**
     * {@inheritdoc}
     */
    public function run($statement, array $parameters = array(), $tag = null)
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
    public function createPipeline($query = null, array $parameters = [], $tag = null)
    {
        return new Pipeline($this);
    }

    /**
     * @param string $statement
     * @param array $parameters
     * @param null|string $tag
     *
     * @return CypherResult
     */
    public function recv($statement, array $parameters = array(), $tag = null)
    {
        return $this->run($statement, $parameters, $tag);
    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        $this->io->init(Driver::getUserAgent(), $this->credentials['user'], $this->credentials['pass']);

        $this->isInitialized = true;
    }

    /**
     * @return \GraphAware\Bolt\PackStream\Structure\Structure
     */
    public function receiveMessageInit()
    {
        throw new BadMethodCallException('Method unsupported');
    }

    /**
     * @return \GraphAware\Bolt\PackStream\Structure\Structure
     */
    public function receiveMessage()
    {
        throw new BadMethodCallException('Method unsupported');
    }

    /**
     * @param \GraphAware\Bolt\Protocol\Message\AbstractMessage $message
     */
    public function sendMessage(AbstractMessage $message)
    {
        throw new BadMethodCallException('Method unsupported');
    }

    /**
     * @param \GraphAware\Bolt\Protocol\Message\AbstractMessage[] $messages
     */
    public function sendMessages(array $messages)
    {
        throw new BadMethodCallException('Method unsupported');
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->io->reset();
        $this->isInitialized = false;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction()
    {
        if ($this->transaction instanceof Transaction) {
            throw new \RuntimeException('A transaction is already bound to this session');
        }

        return new Transaction($this);
    }

    public function commit()
    {
        $this->io->commit();
    }

    public function rollback()
    {
        $this->io->rollback();
    }

    public function begin()
    {
        $this->io->begin();
    }
}
