<?php

namespace Litebase\Laravel\Database\Schema;

use Illuminate\Database\Schema\SQLiteBuilder;

class LitebaseBuilder extends SQLiteBuilder
{
    /**
     * Create a database in the schema.
     *
     * @param  string  $name
     * @return bool
     */
    public function createDatabase($name)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        $tables = $this->connection->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'");

        collect($tables)->each(function ($table) {
            $this->connection->statement(
                sprintf('drop table if exists %s', $this->connection->getSchemaGrammar()->wrapTable($table['name']))
            );
        });
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param  string  $name
     * @return bool
     */
    // public function dropDatabaseIfExists($name)
    // {
    //     throw new \Exception('Not implemented');
    // }

    /**
 * Empty the database file.
 *
 * @return void
 */
    // public function refreshDatabaseFile($path = null)
    // {
    //     throw new \Exception('Not implemented');
    // }
}
