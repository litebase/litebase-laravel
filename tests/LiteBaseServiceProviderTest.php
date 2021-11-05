<?php

namespace Litebase\Tests;

use Litebase\LitebaseConnection;

class LitebaseServiceProviderTest extends TestCase
{
    public function test_the_connection_can_be_resolved()
    {
        $this->assertInstanceOf(
            LitebaseConnection::class,
            $this->app->db->connection('litebase'),
        );
    }
}
