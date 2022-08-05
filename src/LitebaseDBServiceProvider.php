<?php

namespace LitebaseDB;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class LitebaseDBServiceProvider extends ServiceProvider
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
        $this->app->singleton(LitebaseDBClient::class, function ($app) {
            return new LitebaseDBClient($app->config->get('database.connections.litebasedb'));
        });

        Connection::resolverFor('litebasedb', function ($connection, $database, $prefix, $config) {
            return new LitebaseDBConnection($config);
        });

        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }
}
