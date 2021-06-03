<?php

namespace Litebase\Tests;

use Exception;
use Litebase\LitebaseConnector;
use Litebase\LitebasePDO;
use Litebase\Tests\TestCase;

class LitebaseConnectorTest extends TestCase
{
    public function test_it_can_be_created()
    {
        $connector = new LitebaseConnector;

        $this->assertInstanceOf(LitebaseConnector::class, $connector);
    }

    public function test_it_can_connect()
    {
        $connector = new LitebaseConnector;
        $connection = $connector->connect([
            'host' => 'litebase.test',
            'database' => 'testdatabase',
            'key' => 'key',
            'secret' => 'secret',
        ]);

        $this->assertInstanceOf(LitebasePDO::class, $connection);
    }

    public function test_it_cant_connect_with_an_invalid_database_name()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The database identifier contains illegal characters.');
        $connector = new LitebaseConnector;
        $connector->connect([
            'host' => 'litebase.test',
            'database' => 'testdatabase!.,$%',
            'key' => 'key',
            'secret' => 'secret',
        ]);
    }
}
