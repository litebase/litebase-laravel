<?php

namespace Litebase\Tests;

use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\SQLiteBuilder;
use Litebase\LitebaseConnection;

class LitebaseConnectionTest extends TestCase
{
    public function test_it_can_be_created()
    {
        $connection = new LitebaseConnection([
            'host' => 'litebase.test',
            'database' => 'testdatabase',
            'key' => 'key',
            'secret' => 'secret',
        ]);

        $this->assertInstanceOf(LitebaseConnection::class, $connection);
    }

    public function test_it_returns_the_schema_builder()
    {
        $connection = new LitebaseConnection([
            'host' => 'litebase.test',
            'database' => 'testdatabase',
            'key' => 'key',
            'secret' => 'secret',
        ]);

        $this->assertInstanceOf(SQLiteBuilder::class, $connection->getSchemaBuilder());
    }

    public function test_it_returns_the_default_schema_grammar()
    {
        $connection = new LitebaseConnection([
            'host' => 'litebase.test',
            'database' => 'testdatabase',
            'key' => 'key',
            'secret' => 'secret',
        ]);

        $connection->useDefaultSchemaGrammar();

        $this->assertInstanceOf(SQLiteGrammar::class, $connection->getSchemaGrammar());
    }

    public function test_it_returns_the_default_post_processor()
    {
        $connection = new LitebaseConnection([
            'host' => 'litebase.test',
            'database' => 'testdatabase',
            'key' => 'key',
            'secret' => 'secret',
        ]);

        $connection->useDefaultPostProcessor();

        $this->assertInstanceOf(SQLiteProcessor::class, $connection->getPostProcessor());
    }
}
