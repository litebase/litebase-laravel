<?php

namespace LitebaseDB\Tests;

use Orchestra\Testbench\TestCase as TestbenchTestCase;
use LitebaseDB\LitebaseDBServiceProvider;

class TestCase extends TestbenchTestCase
{
    public function afterSetup()
    {
        # code...
    }

    public function afterTest()
    {
        # code...
    }

    public function getEnvironmentSetup($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.connections.litebasedb', [
            'driver' => 'litebasedb',
            'database' => 'test',
            'host' => 'us-east-1.litebase.test',
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [LitebaseDBServiceProvider::class];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->afterSetup();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->afterTest();
    }
}
