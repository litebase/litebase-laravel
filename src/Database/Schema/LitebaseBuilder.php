<?php

namespace Litebase\Laravel\Database\Schema;

use Illuminate\Database\Schema\SQLiteBuilder;
use Litebase\OpenAPI\Model\DatabaseStoreRequest;
use Litebase\OpenAPI\Model\GetDatabase200Response;

class LitebaseBuilder extends SQLiteBuilder
{
    /**
     * The schema grammar instance.
     *
     * @var \Litebase\Laravel\Database\Schema\Grammars\LitebaseGrammar
     */
    protected $grammar;

    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     */
    public function createDatabase($name)
    {
        /** @var \Litebase\ApiClient $client */
        $client = $this->connection->getApiClient();

        try {
            $client->database()->createDatabase(
                new DatabaseStoreRequest(['name' => $name])
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function dropDatabaseIfExists($name)
    {
        /** @var \Litebase\ApiClient $client */
        $client = $this->connection->getApiClient();

        try {
            // Check if database exists first
            $response = $client->database()->getDatabase($name);

            if (!$response instanceof GetDatabase200Response) {
                return false;
            }

            // Drop the database
            $client->database()->deleteDatabase($name);

            return true;
        } catch (\Exception $e) {
            // If database doesn't exist or any error occurs, return false
            return false;
        }
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $tables = $this->connection->select(
            "select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"
        );

        foreach ($tables as $table) {
            $this->connection->statement(
                sprintf(
                    'drop table if exists %s',
                    $this->connection->getSchemaGrammar()->wrapTable($table['name']),
                )
            );
        }
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews()
    {
        $views = $this->connection->select(
            "select name from sqlite_master where type = 'view'"
        );

        foreach ($views as $view) {
            $this->connection->statement(
                sprintf(
                    'drop view if exists %s',
                    $this->connection->getSchemaGrammar()->wrapTable($view['name']),
                )
            );
        }
    }

    /** @inheritDoc */
    public function getTables($schema = null)
    {
        return $this->connection->getPostProcessor()->processTables(
            $this->connection->selectFromWriteConnection(
                $this->grammar->compileTables(
                    schema: $schema,
                    withSize: true,
                )
            )
        );
    }
}
