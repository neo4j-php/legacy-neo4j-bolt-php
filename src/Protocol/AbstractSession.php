<?php

/*
 * This file is part of the GraphAware Bolt package.
 *
 * (c) GraphAware Ltd <christophe@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Bolt\Protocol;

use Bolt\Bolt;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractSession implements SessionInterface
{
    /**
     * @var Bolt
     */
    protected $io;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param Bolt $bolt
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(Bolt $bolt, EventDispatcherInterface $dispatcher)
    {
        $this->io = $bolt;
        $this->dispatcher = $dispatcher;
    }
}
