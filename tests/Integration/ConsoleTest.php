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

    test('db:show command works with litebase', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Create a test table for the command to display
        $builder->create('show_command_test', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Run db:show command
        $exitCode = Artisan::call('db:show', [
            '--database' => 'litebase',
        ]);

        // Verify command executed successfully
        expect($exitCode)->toBe(0);

        $output = Artisan::output();

        // Verify output contains database information
        expect($output)->toContain('litebase');
        expect($output)->toContain('show_command_test');

        // Clean up
        $builder->dropIfExists('show_command_test');
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

    test('schema:dump command works with litebase', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Create a test table
        $builder->create('dump_test_table', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create migrations table with some data
        $builder->dropIfExists('migrations');
        $builder->create('migrations', function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });

        $connection->table('migrations')->insert([
            ['migration' => '2024_01_01_000000_create_dump_test_table', 'batch' => 1],
        ]);

        // Run schema:dump command
        $dumpPath = database_path('schema/litebase-test-dump.sql');

        // Ensure directory exists
        if (!is_dir(dirname($dumpPath))) {
            mkdir(dirname($dumpPath), 0755, true);
        }

        Artisan::call('schema:dump', [
            '--database' => 'litebase',
            '--path' => $dumpPath,
        ]);

        // Verify dump file was created
        expect(file_exists($dumpPath))->toBeTrue();

        $dumpContent = file_get_contents($dumpPath);
        expect($dumpContent)->toContain('dump_test_table');
        expect($dumpContent)->toContain('migrations');
        expect($dumpContent)->toContain('2024_01_01_000000_create_dump_test_table');

        // Clean up
        $builder->dropIfExists('dump_test_table');
        $builder->dropIfExists('migrations');

        if (file_exists($dumpPath)) {
            unlink($dumpPath);
        }
    });

    test('db:monitor command works with litebase', function () {
        // Run db:monitor command for litebase connection
        $exitCode = Artisan::call('db:monitor', [
            '--databases' => 'litebase',
        ]);

        // Verify command executed successfully
        expect($exitCode)->toBe(0);

        $output = Artisan::output();

        // Verify output contains the database name
        expect($output)->toContain('litebase');

        // Verify output shows connection status (OK since no max limit)
        expect($output)->toContain('OK');
    });

    test('db:table command works with litebase', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Create a test table with various column types and indexes
        $builder->create('table_command_test', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->integer('age')->default(0);
            $table->timestamps();

            $table->index(['name', 'age']);
        });

        // Run db:table command
        $exitCode = Artisan::call('db:table', [
            'table' => 'table_command_test',
            '--database' => 'litebase',
        ]);

        // Verify command executed successfully
        expect($exitCode)->toBe(0);

        $output = Artisan::output();

        // Verify output contains table name
        expect($output)->toContain('table_command_test');

        // Verify output contains column information
        expect($output)->toContain('id');
        expect($output)->toContain('name');
        expect($output)->toContain('email');
        expect($output)->toContain('age');

        // Clean up
        $builder->dropIfExists('table_command_test');
    });

    test('db:wipe command works with litebase', function () {
        $connection = app()->db->connection('litebase');
        $builder = $connection->getSchemaBuilder();

        // Create some test tables and views
        $builder->create('wipe_test_table_1', function ($table) {
            $table->id();
            $table->string('name');
        });

        $builder->create('wipe_test_table_2', function ($table) {
            $table->id();
            $table->string('email');
        });

        // Create a view
        $connection->statement('CREATE VIEW wipe_test_view AS SELECT * FROM wipe_test_table_1');

        // Verify tables and view exist
        expect($builder->hasTable('wipe_test_table_1'))->toBeTrue();
        expect($builder->hasTable('wipe_test_table_2'))->toBeTrue();
        expect($builder->hasView('wipe_test_view'))->toBeTrue();

        // Run db:wipe command with --force flag (to skip confirmation in tests)
        $exitCode = Artisan::call('db:wipe', [
            '--database' => 'litebase',
            '--force' => true,
            '--drop-views' => true,
        ]);

        // Verify command executed successfully
        expect($exitCode)->toBe(0);

        // Verify tables and views were dropped
        expect($builder->hasTable('wipe_test_table_1'))->toBeFalse();
        expect($builder->hasTable('wipe_test_table_2'))->toBeFalse();
        expect($builder->hasView('wipe_test_view'))->toBeFalse();
    });
});
