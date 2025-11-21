<?php

namespace Litebase\Laravel;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Filesystem\Filesystem;
use Litebase\ApiClient;
use Litebase\Configuration;
use Litebase\LitebaseClient;
use Litebase\LitebasePDO;
use Litebase\Laravel\Database\Schema\Grammars\LitebaseGrammar;
use Litebase\Laravel\Database\Schema\LitebaseBuilder;
use Litebase\Laravel\Database\Schema\LitebaseSchemaState;

class LitebaseConnection extends Connection
{
    /**
     * The Litebase API client.
     *
     * @var \Litebase\ApiClient
     */
    protected $apiClient;

    /**
     * The Litebase configuration instance.
     */
    protected Configuration $configuration;

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

        $this->configuration = Configuration::create($this->config);
        $this->apiClient = new ApiClient($this->configuration);
        $this->pdo = new LitebasePDO(new LitebaseClient($this->configuration));

        parent::__construct(
            pdo: $this->pdo,
            database: $database,
            tablePrefix: $tablePrefix,
            config: $this->config
        );
    }

    /**
     * Get the Litebase API client instance.
     *
     * @return \Litebase\ApiClient
     */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'Litebase';
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

    /**
     * Get the schema state for the connection.
     *
     * @param  \Illuminate\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     *
     * @throws \RuntimeException
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new LitebaseSchemaState($this, $files, $processFactory);
    }
}
