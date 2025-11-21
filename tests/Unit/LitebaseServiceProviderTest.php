<?php

namespace Tests\Unit;

use Litebase\Laravel\LitebaseConnection;

test('the connection can be resolved', function () {
    expect(app()->db->connection('litebase'))
        ->toBeInstanceOf(LitebaseConnection::class);
});

test('connection receives correct parameters', function () {
    config(['database.connections.litebase' => [
        'driver' => 'litebase',
        'database' => 'test/main',
        'prefix' => 'test_',
    ]]);

    $connection = app()->db->connection('litebase');

    expect($connection)->toBeInstanceOf(LitebaseConnection::class)
        ->and($connection->getDatabaseName())->toBe('test/main')
        ->and($connection->getTablePrefix())->toBe('test_');
});

test('multiple litebase connections can be created', function () {
    config(['database.connections.litebase1' => [
        'driver' => 'litebase',
        'database' => 'test/main',
    ]]);

    config(['database.connections.litebase2' => [
        'driver' => 'litebase',
        'database' => 'test/staging',
    ]]);

    $connection1 = app()->db->connection('litebase1');
    $connection2 = app()->db->connection('litebase2');

    expect($connection1)->toBeInstanceOf(LitebaseConnection::class)
        ->and($connection2)->toBeInstanceOf(LitebaseConnection::class)
        ->and($connection1)->not->toBe($connection2);
});

test('litebase db command is registered', function () {
    $commands = \Illuminate\Support\Facades\Artisan::all();

    expect($commands)->toHaveKey('litebase:db');
    expect($commands['litebase:db'])->toBeInstanceOf(\Litebase\Laravel\Console\LitebaseDbCommand::class);
});
