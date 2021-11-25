<?php

namespace LitebaseDB\Tests;

use LitebaseDB\LitebaseDBConnection;

class LitebaseDBServiceProviderTest extends TestCase
{
    public function test_the_connection_can_be_resolved()
    {
        $this->assertInstanceOf(
            LitebaseDBConnection::class,
            $this->app->db->connection('litebasedb'),
        );
    }
}
