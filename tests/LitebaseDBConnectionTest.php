<?php

namespace LitebaseDB\Tests;

use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\SQLiteBuilder;
use LitebaseDB\LitebaseDBConnection;

class LitebaseDBConnectionTest extends TestCase
{
    public function test_it_can_be_created()
    {
        $connection = new LitebaseDBConnection([
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'database' => 'test',
            'host' => 'us-east.litebase.test',
        ]);

        $this->assertInstanceOf(LitebaseDBConnection::class, $connection);
    }

    public function test_it_returns_the_schema_builder()
    {
        $connection = new LitebaseDBConnection([
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'database' => 'test',
            'host' => 'us-east.litebase.test',
        ]);

        $this->assertInstanceOf(SQLiteBuilder::class, $connection->getSchemaBuilder());
    }

    public function test_it_returns_the_default_schema_grammar()
    {
        $connection = new LitebaseDBConnection([
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'database' => 'test',
            'host' => 'us-east.litebase.test',
        ]);

        $connection->useDefaultSchemaGrammar();

        $this->assertInstanceOf(SQLiteGrammar::class, $connection->getSchemaGrammar());
    }

    public function test_it_returns_the_default_post_processor()
    {
        $connection = new LitebaseDBConnection([
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'database' => 'test',
            'host' => 'us-east.litebase.test',
        ]);

        $connection->useDefaultPostProcessor();

        $this->assertInstanceOf(SQLiteProcessor::class, $connection->getPostProcessor());
    }
}
