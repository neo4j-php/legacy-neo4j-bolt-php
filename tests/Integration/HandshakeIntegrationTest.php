<?php

namespace GraphAware\Bolt\Tests\Integration;

use GraphAware\Bolt\Protocol\SessionInterface;
use GraphAware\Bolt\Tests\IntegrationTestCase;

/**
 * Class HandshakeIntegrationTest
 * @package GraphAware\Bolt\Tests\Integration
 *
 * @group integration
 * @group handshake
 */
class HandshakeIntegrationTest extends IntegrationTestCase
{
    public function testHandshakeAgreeVersion()
    {
        $session = $this->getSession();
        $this->assertInstanceOf(SessionInterface::class, $session);
        $this->assertEquals(1, $session::getProtocolVersion());
    }
}
