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

use GraphAware\Bolt\Protocol\V1\Transaction;
use GraphAware\Bolt\Result\Result;
use GraphAware\Common\Driver\SessionInterface as BaseSessionInterface;

interface SessionInterface extends BaseSessionInterface
{
    public static function getProtocolVersion(): string;

    /**
     * @param $statement
     * @param array $parameters
     * @param null $tag
     * @return Result
     */
    public function run($statement, array $parameters = array(), $tag = null): Result;

    /**
     * @return mixed
     */
    public function runPipeline(Pipeline $pipeline);

    public function createPipeline($query = null, array $parameters = array(), $tag = null): Pipeline;

    public function transaction(): Transaction;
}
