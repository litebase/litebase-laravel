<?php

namespace Litebase\Tests;

use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Litebase\LitebaseServiceProvider;

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

        $app['config']->set('database.connections.litebase', [
            'driver'   => 'litebase',
            'host' => 'litebase.test',
            'database' => 'testdatabase',
            'key' => 'key',
            'secret' => 'secret',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [LitebaseServiceProvider::class];
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
