<?php

namespace Tests;

use Litebase\Laravel\LitebaseServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    public function getEnvironmentSetup($app)
    {
        $app['config']->set('database.connections.litebase', [
            'driver' => 'litebase',
            'database' => 'test/main',
            'host' => 'http://localhost:8888',
            'username' => 'root',
            'password' => 'password',
            'host' => '127.0.0.1',
            'port' => '8888',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [LitebaseServiceProvider::class];
    }
}
