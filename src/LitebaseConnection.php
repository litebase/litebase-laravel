<?php

namespace Litebase\Laravel;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Filesystem\Filesystem;
use Litebase\ApiClient;
use Litebase\Configuration;
use Litebase\Laravel\Database\Schema\Grammars\LitebaseGrammar;
use Litebase\Laravel\Database\Schema\LitebaseBuilder;
use Litebase\Laravel\Database\Schema\LitebaseSchemaState;
use Litebase\LitebaseClient;
use Litebase\LitebasePDO;
use PDO;

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
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function __construct(string $database, string $tablePrefix, array $config = [])
    {
        $this->config = $config;

        /** @var array<string, string|null> $configForLitebase */
        $configForLitebase = array_map(function ($value) {
            if (is_null($value)) {
                return null;
            }
            if (is_string($value)) {
                return $value;
            }
            if (is_scalar($value)) {
                return (string) $value;
            }

            return null;
        }, $this->config);
        $this->configuration = Configuration::create($configForLitebase);
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
        $this->useDefaultSchemaGrammar();

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
     * @return \Litebase\Laravel\Database\Schema\LitebaseSchemaState
     *
     * @throws \RuntimeException
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new LitebaseSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the server version for the connection.
     */
    public function getServerVersion(): string
    {
        $version = $this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        assert(is_string($version));

        return $version;
    }
}
