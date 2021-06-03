<?php

namespace Litebase;

use Exception;
use Illuminate\Database\Connectors\Connector;
use Litebase\LitebasePDO;
use PDO;

class LitebaseConnector extends Connector
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     *
     * @throws \InvalidArgumentException
     */
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        $dsn = $config['database'];

        return $this->createConnection($dsn, $config, $options);
    }

    /**
     * Create a new PDO connection.
     *
     * @param  string  $dsn
     * @param  array  $config
     * @param  array  $options
     * @return \PDO
     *
     * @throws \Exception
     */
    public function createConnection($dsn, array $config, array $options)
    {
        try {
            return $this->createPdoConnection(
                $config['host'],
                $config['database'],
                $config['key'],
                $config['secret'],
                $options
            );
        } catch (Exception $e) {
            return $this->tryAgainIfCausedByLostConnection($e, $dsn, $config['username'], $config['password'], $options);
        }
    }

    /**
     * Create a new PDO connection instance.
     *
     * @param  string  $database
     * @param  string  $username
     * @param  string  $password
     * @param  array  $options
     * @return \PDO
     */
    protected function createPdoConnection($database, $username, $password, $options)
    {
        return new LitebasePDO($database, $username, $password, $options);
    }
}
