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
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [LitebaseServiceProvider::class];
    }
}
