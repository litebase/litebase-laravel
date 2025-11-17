<?php

namespace Litebase\Laravel;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Litebase\Laravel\Database\Schema\Grammars\LitebaseGrammar;
use Litebase\Laravel\Database\Schema\LitebaseBuilder;
use Litebase\LitebasePDO;

class LitebaseConnection extends Connection
{
    /**
     * The active PDO connection.
     *
     * @var \Litebase\LitebasePDO
     */
    protected $pdo;

    /**
     * Create a new database connection instance.
     *
     * @return void
     */
    public function __construct(string $database, string $tablePrefix, array $config = [])
    {
        $this->config = $config;

        $this->pdo = new LitebasePDO($this->config);

        parent::__construct(
            pdo: $this->pdo,
            database: $database,
            tablePrefix: $tablePrefix,
            config: $this->config
        );
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

        return new LitebaseBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\SQLiteGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new LitebaseGrammar($this);
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
