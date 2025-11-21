<?php

namespace Litebase\Laravel;

use Illuminate\Database\Connection;
use Illuminate\Database\Console\DbCommand;
use Illuminate\Support\ServiceProvider;
use Litebase\Laravel\Console\LitebaseDbCommand;

class LitebaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Connection::resolverFor('litebase', function ($connection, $database, $prefix, $config) {
            return new LitebaseConnection($database, $prefix, $config);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                LitebaseDbCommand::class,
            ]);
        }
    }
}
