<?php

namespace Litebase;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

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
        $this->app->singleton(LitebaseClient::class, function ($app) {
            return new LitebaseClient($app->config->get('database.connections.litebase'));
        });

        Connection::resolverFor('litebase', function ($connection, $database, $prefix, $config) {
            return new LitebaseConnection($config);
        });

        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }
}
