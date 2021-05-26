<?php

/*
 * This file is part of the GraphAware Bolt package.
 *
 * (c) Graph Aware Limited <http://graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Bolt;

use Bolt\Bolt;
use GraphAware\Bolt\Exception\IOException;
use GraphAware\Bolt\IO\StreamSocket;
use GraphAware\Bolt\Protocol\SessionRegistry;
use GraphAware\Bolt\PackStream\Packer;
use GraphAware\Bolt\Protocol\V1\Session;
use GraphAware\Common\Driver\DriverInterface;
use phpDocumentor\Reflection\Types\Static_;
use Symfony\Component\EventDispatcher\EventDispatcher;
use GraphAware\Bolt\Exception\HandshakeException;
use function parse_url;

class Driver implements DriverInterface
{
    const VERSION = '1.5.4';

    const DEFAULT_TCP_PORT = 7687;

    /**
     * @var Bolt
     */
    protected $io;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var SessionRegistry
     */
    protected $sessionRegistry;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * @return string
     */
    public static function getUserAgent()
    {
        return 'GraphAware-BoltPHP/'.self::VERSION;
    }

    /**
     * @param string             $uri
     * @param Configuration|null $configuration
     */
    public function __construct($uri, Configuration $configuration = null)
    {
        $configuration = $configuration === null ? Configuration::create() : $configuration;
        $this->credentials = null !== $configuration ? $configuration->getValue('credentials', []) : [];
        /*
        $ctx = stream_context_create(array());
        define('CERTS_PATH',
        '/Users/ikwattro/dev/_graphs/3.0-M02-NIGHTLY/conf');
        $ssl_options = array(
            'cafile' => CERTS_PATH . '/cacert.pem',
            'local_cert' => CERTS_PATH . '/ssl/snakeoil.pem',
            'peer_name' => 'example.com',
            'allow_self_signed' => true,
            'verify_peer' => true,
            'capture_peer_cert' => true,
            'capture_peer_cert_chain' => true,
            'disable_compression' => true,
            'SNI_enabled' => true,
            'verify_depth' => 1
        );
        foreach ($ssl_options as $k => $v) {
            stream_context_set_option($ctx, 'ssl', $k, $v);
        }
        */

        $parsedUri = parse_url($uri);

        $this->credentials['user'] = isset($this->credentials['user']) ? $this->credentials['user'] : $configuration->getValue('user', '');
        $this->credentials['pass'] = isset($this->credentials['pass']) ? $this->credentials['pass'] : $configuration->getValue('password', '');
        $host = isset($parsedUri['host']) ? $parsedUri['host'] : $parsedUri['path'];
        $port = isset($parsedUri['port']) ? $parsedUri['port'] : static::DEFAULT_TCP_PORT;
        $this->dispatcher = new EventDispatcher();
        $this->io = new Bolt(new \Bolt\connection\StreamSocket($host, $port, $configuration->getValue('timeout', 15)));
        $this->sessionRegistry = new SessionRegistry($this->io, $this->dispatcher);
        $this->sessionRegistry->registerSession(Session::class);
    }

    /**
     * @return Session
     */
    public function session()
    {
        return new Session($this->io, $this->dispatcher, $this->credentials);
    }

    public function handshake()
    {
        if ($this->credentials['user'] === '') {
            $this->io->setScheme('none');
        } else {
            $this->io->setScheme('basic');
        }

        $this->io->init(static::getUserAgent(), $this->credentials['user'], $this->credentials['pass']);
    }
}
