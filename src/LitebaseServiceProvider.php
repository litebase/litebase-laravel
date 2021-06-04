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
        $this->app->bind('db.connector.litebase', function ($app) {
            return new LitebaseConnector;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Connection::resolverFor('litebase', function ($connection, $database, $prefix, $config) {
            $connection = new LitebaseConnection($config);
            // @todo: Need to ensure this is configurable.
            $connection->getPdo()->pendingConnection();

            return $connection;
        });

        if ($this->app->runningInConsole()) {
            $this->commands([ServeCommand::class]);
        }
    }
}
