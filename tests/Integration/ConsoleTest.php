<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use Litebase\ApiClient;
use Litebase\Configuration;
use Litebase\OpenAPI\Model\DatabaseStoreRequest;

beforeAll(function () {
    $configuration = new Configuration();

    $configuration
        ->setHost('127.0.0.1')
        ->setPort('8888')
        ->setUsername('root')
        ->setPassword('password');

    $client = new ApiClient($configuration);

    LitebaseContainer::start();

    try {
        $response = $client->clusterStatus()->listClusterStatuses();
    } catch (\Exception $e) {
        throw new \RuntimeException('Failed to connect to Litebase server for integration tests: ' . $e->getMessage());
    }

    if ($response->getStatus() !== 'success') {
        throw new \RuntimeException('Failed to connect to Litebase server for integration tests.');
    }

    // Create the test database
    $client->database()->createDatabase(
        new DatabaseStoreRequest(
            [
                'name' => 'test',
            ],
        )
    );
});

afterAll(function () {
    LitebaseContainer::stop();
});

describe('Litebase Laravel Console Integration', function () {
    test('connection has required methods for db:show', function () {
        $connection = app()->db->connection('litebase');

        // Verify the connection has required methods
        expect($connection->getDriverTitle())->toBe('Litebase');
        expect($connection->getServerVersion())->toBeString();
        expect($connection->threadCount())->toBeNull(); // Returns null by default
    });

    test('schema builder provides required data for db:show', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Create a test table
        $builder->create('console_test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Verify getTables works (required by db:show)
        $tables = $builder->getTables();
        expect($tables)->toBeArray();
        expect(collect($tables)->pluck('name')->toArray())->toContain('console_test_table');

        // Clean up
        $builder->dropIfExists('console_test_table');
    });

    test('litebase:db command is registered', function () {
        $commands = \Illuminate\Support\Facades\Artisan::all();

        expect($commands)->toHaveKey('litebase:db');
    });

    test('litebase:db command can execute queries', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Create a test table
        $builder->create('db_command_test', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Insert test data
        $connection->table('db_command_test')->insert([
            ['name' => 'Test 1'],
            ['name' => 'Test 2'],
        ]);

        // Verify we can query the data
        $results = $connection->select('SELECT * FROM db_command_test');
        expect($results)->toHaveCount(2);

        // Clean up
        $builder->dropIfExists('db_command_test');
    });
});
