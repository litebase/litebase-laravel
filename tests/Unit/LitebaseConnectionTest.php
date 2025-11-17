<?php

namespace Tests\Unit;

use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Litebase\Laravel\Database\Schema\Grammars\LitebaseGrammar;
use Litebase\Laravel\Database\Schema\LitebaseBuilder;
use Litebase\Laravel\LitebaseConnection;
use Litebase\LitebasePDO;

test('it can be created', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => '',
        ]
    );

    expect($connection)->toBeInstanceOf(LitebaseConnection::class);
});

test('it creates a LitebasePDO instance', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );

    expect($connection->getPdo())->toBeInstanceOf(LitebasePDO::class);
});

test('it stores the database name', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );

    expect($connection->getDatabaseName())->toBe('test/main');
});

test('it stores the table prefix', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: 'test_',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );

    expect($connection->getTablePrefix())->toBe('test_');
});

test('it stores the config', function () {
    $config = [
        'access_key_id' => 'key',
        'access_key_secret' => 'secret',
        'host' => 'http://localhost:8888',
    ];

    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: $config
    );

    expect($connection->getConfig())->toBe($config);
});

test('it returns the schema builder', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );

    expect($connection->getSchemaBuilder())->toBeInstanceOf(LitebaseBuilder::class);
});

test('it returns the default schema grammar', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );
    $connection->useDefaultSchemaGrammar();

    expect($connection->getSchemaGrammar())->toBeInstanceOf(LitebaseGrammar::class);
});

test('it returns the default post processor', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );

    $connection->useDefaultPostProcessor();

    expect($connection->getPostProcessor())->toBeInstanceOf(SQLiteProcessor::class);
});

test('it can handle empty table prefix', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );

    expect($connection->getTablePrefix())->toBe('');
});

test('schema builder uses the same connection instance', function () {
    $connection = new LitebaseConnection(
        database: 'test/main',
        tablePrefix: '',
        config: [
            'access_key_id' => 'key',
            'access_key_secret' => 'secret',
            'host' => 'http://localhost:8888',
        ]
    );

    $builder = $connection->getSchemaBuilder();

    expect($builder->getConnection())->toBe($connection);
});
