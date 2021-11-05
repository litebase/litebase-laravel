<?php

namespace Litebase;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as SchemaGrammar;
use Litebase\LitebasePDO;

class LitebaseConnection extends Connection
{
    /**
     * The active PDO connection.
     *
     * @var LitebasePDO
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

        $this->pdo = new LitebasePDO($config);

        parent::__construct($this->pdo, $config['database'], '', $this->config);
    }

    /**
     * Get the current PDO connection.
     */
    public function getPdo(): LitebasePDO
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
