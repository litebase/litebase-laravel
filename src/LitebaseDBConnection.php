<?php

namespace LitebaseDB;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as SchemaGrammar;
use Illuminate\Database\Schema\SQLiteBuilder;
// use LitebaseDB\LitebaseDBPDO;

class LitebaseDBConnection extends Connection
{
    /**
     * The active PDO connection.
     *
     * @var \LitebaseDB\LitebaseDBPDO
     */
    protected $pdo;

    /**
     * Create a new database connection instance.
     *
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        $this->pdo = new LitebaseDBPDO($config);

        parent::__construct($this->pdo, '', '', $this->config);
    }

    /**
     * Get the current PDO connection.
     */
    public function getPdo(): LitebaseDBPDO
    {
        return $this->pdo;
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\SQLiteBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SQLiteBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\SQLiteGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar;
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\SQLiteProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SQLiteProcessor;
    }
}
