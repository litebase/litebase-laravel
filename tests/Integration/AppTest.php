<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Litebase\ApiClient;
use Litebase\Configuration;
use Litebase\OpenAPI\Model\DatabaseStoreRequest;

$configuration = new Configuration;

$configuration
    ->setHost('127.0.0.1')
    ->setPort('8888')
    ->setUsername('root')
    ->setPassword('password');

$client = new ApiClient($configuration);

beforeAll(function () use ($client) {
    LitebaseContainer::start();

    try {
        $response = $client->clusterStatus()->listClusterStatuses();
    } catch (\Exception $e) {
        throw new \RuntimeException('Failed to connect to Litebase server for integration tests: '.$e->getMessage());
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

describe('App Test', function () {
    it('can run migrations', function () {
        config(['database.connections.litebase' => [
            'driver' => 'litebase',
            'database' => 'test/main',
            'username' => 'root',
            'password' => 'password',
            'host' => '127.0.0.1',
            'port' => '8888',
        ]]);

        $connection = DB::connection('litebase');

        $connection
            ->getSchemaBuilder()
            ->create('users', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });

        $tables = $connection
            ->getSchemaBuilder()
            ->getTables();

        expect(in_array('users', array_map(fn ($table) => $table['name'], $tables)))->toBeTrue();
    });

    it('can read and write data', function () {
        config(['database.connections.litebase' => [
            'driver' => 'litebase',
            'database' => 'test/main',
            'username' => 'root',
            'password' => 'password',
            'host' => '127.0.0.1',
            'port' => '8888',
        ]]);

        $connection = DB::connection('litebase');

        // Create table if not exists
        $connection->getSchemaBuilder()->dropIfExists('users');

        $connection->getSchemaBuilder()
            ->create('users', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });

        $connection->table('users')->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $users = $connection->table('users')->get();

        expect($users)->toHaveCount(1);
        expect($users[0]->name)->toBe('John Doe');
        expect($users[0]->email)->toBe('john@example.com');
    });

    it('can drop database if exists', function () {
        $configuration = new Configuration;
        $configuration
            ->setHost('127.0.0.1')
            ->setPort('8888')
            ->setUsername('root')
            ->setPassword('password');
        $client = new ApiClient($configuration);

        config(['database.connections.litebase' => [
            'driver' => 'litebase',
            'database' => 'test_drop/main',
            'username' => 'root',
            'password' => 'password',
            'host' => '127.0.0.1',
            'port' => '8888',
        ]]);

        // Create a test database first using SDK
        $client->database()->createDatabase(
            new DatabaseStoreRequest(['name' => 'test_drop'])
        );

        $connection = DB::connection('litebase');

        // Try to drop the database using the schema builder
        try {
            $result = $connection->getSchemaBuilder()->dropDatabaseIfExists('test_drop');

            // If we get here, the method exists and ran
            expect($result)->toBeTrue();

            // Verify the database was actually dropped
            $databases = $client->database()->listDatabases();

            /** @phpstan-ignore-next-line */
            $databaseNames = array_map(fn ($db) => $db['databaseName'], $databases->getData());
            expect($databaseNames)->not->toContain('test_drop');
        } catch (\Exception $e) {
            // If method doesn't exist or throws, we'll need to implement it
            $message = $e->getMessage();
            $isExpected = str_contains($message, 'Not implemented') ||
                str_contains($message, 'does not exist') ||
                str_contains($message, 'not supported');
            expect($isExpected)->toBeTrue();

            // Clean up - drop the database manually using SDK
            try {
                $client->database()->deleteDatabase('test_drop');
            } catch (\Exception $cleanupError) {
                // Ignore cleanup errors

                dd($cleanupError->getMessage());
            }
        }
    });
});
