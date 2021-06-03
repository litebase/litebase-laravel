<?php

namespace Litebase\Tests;

use Litebase\LitebaseConnection;
use Litebase\LitebaseConnector;

class LitebaseServiceProviderTest extends TestCase
{
    public function test_it_binds_the_litebase_connector()
    {
        $this->assertTrue($this->app->bound('db.connector.litebase'));

        $this->assertInstanceOf(
            LitebaseConnector::class,
            $this->app->make('db.connector.litebase')
        );
    }

    public function test_the_connection_can_be_resolved()
    {
        $this->assertInstanceOf(
            LitebaseConnection::class,
            $this->app->db->connection('litebase'),
        );
    }
}
