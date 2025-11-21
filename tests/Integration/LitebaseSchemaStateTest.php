<?php

namespace Tests\Integration;

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


describe('LitebaseSchemaState', function () {
    test('it can dump and load the schema state', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Create a test table with data
        $builder->create('schema_state_test', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Insert some data
        $connection->table('schema_state_test')->insert([
            'name' => 'Test Entry',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $schemaState = $connection->getSchemaState();

        // Dump the current schema state to a file
        $dumpFile = __DIR__ . '/schema_dump.sql';
        $schemaState->dump($connection, $dumpFile);

        expect(file_exists($dumpFile))->toBeTrue();
        expect(file_get_contents($dumpFile))->toContain('schema_state_test');

        // Drop the table
        $builder->drop('schema_state_test');
        expect($builder->hasTable('schema_state_test'))->toBeFalse();

        // Now, load the schema state from the dump file
        $schemaState->load($dumpFile);

        // Verify the table was recreated
        expect($builder->hasTable('schema_state_test'))->toBeTrue();

        // Clean up
        $builder->dropIfExists('schema_state_test');
        unlink($dumpFile);
    });

    test('it can dump and load schema with migrations table', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Ensure clean slate - drop if exists
        $builder->dropIfExists('migrations');

        // Create migrations table
        $builder->create('migrations', function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });

        // Insert migration records
        $connection->table('migrations')->insert([
            ['migration' => '2024_01_01_000000_create_users_table', 'batch' => 1],
            ['migration' => '2024_01_02_000000_create_posts_table', 'batch' => 1],
        ]);

        $schemaState = $connection->getSchemaState();

        // Dump the schema
        $dumpFile = __DIR__ . '/schema_with_migrations.sql';
        $schemaState->dump($connection, $dumpFile);

        $dumpContent = file_get_contents($dumpFile);
        expect($dumpContent)->toContain('migrations');
        expect($dumpContent)->toContain('2024_01_01_000000_create_users_table');

        // Drop migrations table to simulate a fresh load
        $builder->drop('migrations');
        expect($builder->hasTable('migrations'))->toBeFalse();

        // Load the schema
        $schemaState->load($dumpFile);

        // Verify migrations table and data were restored
        expect($builder->hasTable('migrations'))->toBeTrue();
        expect($connection->table('migrations')->count())->toBe(2);
        expect($connection->table('migrations')->where('migration', '2024_01_01_000000_create_users_table')->exists())->toBeTrue();

        // Clean up
        $builder->dropIfExists('migrations');
        unlink($dumpFile);
    });
});
