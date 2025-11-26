<?php

namespace Tests;

use Litebase\Laravel\LitebaseServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function getEnvironmentSetup($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app['config'];
        $config->set('database.connections.litebase', [
            'driver' => 'litebase',
            'database' => 'test/main',
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'password',
            'port' => '8888',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [LitebaseServiceProvider::class];
    }
}
