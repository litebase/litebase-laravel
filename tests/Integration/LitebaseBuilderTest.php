<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Database\Schema\Blueprint;
use Litebase\ApiClient;
use Litebase\Configuration;
use Litebase\Laravel\Database\Schema\LitebaseBuilder;
use Litebase\OpenAPI\Model\DatabaseStoreRequest;
use RuntimeException;

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

describe('LitebaseBuilder', function () {
    it('is an instance of LitebaseBuilder', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        expect($builder)->toBeInstanceOf(LitebaseBuilder::class);
    });

    it('can create tables', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        expect($builder->hasTable('test_table'))->toBeTrue();
    });

    test('createDatabase', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        expect($builder->createDatabase('test_create_database'))->toBeTrue();
        expect($builder->createDatabase('test_create_database'))->toBeFalse();
    });

    test('disableForeignKeyConstraints', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        expect($builder->disableForeignKeyConstraints())->toBeTrue();
    });

    test('drop', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_drop_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        expect($builder->drop('test_drop_table'))->toBeNull();
        expect($builder->hasTable('test_drop_table'))->toBeFalse();
    });

    test('dropIfExists', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_drop_if_exists_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        expect($builder->dropIfExists('test_drop_if_exists_table'))->toBeNull();
        expect($builder->dropIfExists('non_existent_table'))->toBeNull();
        expect($builder->hasTable('test_drop_if_exists_table'))->toBeFalse();
    });

    test('dropAllTables', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_table_one', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $builder->create('test_table_two', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        expect($builder->dropAllTables())->toBeNull();
        expect($builder->hasTable('test_table_one'))->toBeFalse();
        expect($builder->hasTable('test_table_two'))->toBeFalse();
    });

    test('dropAllViews', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        // Create a table
        $builder->create('test_view_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        // Create a view
        app()->db->connection('litebase')->statement('CREATE VIEW test_view AS SELECT * FROM test_view_table');

        expect($builder->dropAllViews())->toBeNull();
        expect($builder->hasView('test_view'))->toBeFalse();
    });

    test('dropColumns', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_drop_columns_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        expect($builder->dropColumns('test_drop_columns_table', ['email']))->toBeNull();

        $columns = $builder->getColumns('test_drop_columns_table');

        expect(collect($columns)->pluck('name')->contains('email'))->toBeFalse();
    });

    test('dropDatabaseIfExists', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        expect($builder->dropDatabaseIfExists('test_drop_database_if_exists'))->toBeFalse();

        // Create the database first
        $builder->createDatabase('test_drop_database_if_exists');

        expect($builder->dropDatabaseIfExists('test_drop_database_if_exists'))->toBeTrue();
    });

    test('enableForeignKeyConstraints', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        expect($builder->enableForeignKeyConstraints())->toBeTrue();
    });

    test('getColumns', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_get_columns_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $columns = $builder->getColumns('test_get_columns_table');

        expect(collect($columns)->pluck('name')->all())
            ->toEqual(['id', 'name', 'created_at', 'updated_at']);
    });

    test('getForeignKeys', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_foreign_keys_parent', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $builder->create('test_foreign_keys_child', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('test_foreign_keys_parent');
        });

        $foreignKeys = $builder->getForeignKeys('test_foreign_keys_child');

        expect($foreignKeys)
            ->toEqual([
                [
                    'name' => null,
                    'columns' => ['parent_id'],
                    'foreign_schema' => 'main',
                    'foreign_table' => 'test_foreign_keys_parent',
                    'foreign_columns' => ['id'],
                    'on_update' => 'no action',
                    'on_delete' => 'no action'
                ]
            ]);
    });

    test('getIndexes', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_indexes_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('email');
            $table->timestamps();

            $table->index('email');
        });

        $indexes = $builder->getIndexes('test_indexes_table');

        expect($indexes)
            ->toEqual([
                [
                    'name' => 'primary',
                    'unique' => true,
                    'columns' => ['id'],
                    'type' => null,
                    'primary' => true,
                ],
                [
                    'name' => 'test_indexes_table_email_index',
                    'unique' => false,
                    'columns' => ['email'],
                    'type' => null,
                    'primary' => false,
                ],
                [
                    'name' => 'test_indexes_table_name_unique',
                    'unique' => true,
                    'columns' => ['name'],
                    'type' => null,
                    'primary' => false,
                ],
            ]);
    });

    test('getSchemas', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $schemas = $builder->getSchemas();

        expect(collect($schemas)->pluck('name')->all())->toContain('main');
    });

    test('getTables', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_get_tables_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $tables = $builder->getTables();

        expect(collect($tables)->pluck('name')->all())->toContain('test_get_tables_table');
    });

    test('getViews', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        // Create a table
        $builder->create('test_get_views_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        // Create a view
        app()->db->connection('litebase')->statement('CREATE VIEW test_get_views AS SELECT * FROM test_get_views_table');

        $views = $builder->getViews();

        expect(collect($views)->pluck('name')->all())->toContain('test_get_views');
    });

    test('getTypes', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $types = $builder->getTypes();

        expect($types)->toEqual([]);
    })->throws(RuntimeException::class, 'This database driver does not support retrieving user-defined types.');


    test('hasTable', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        expect($builder->hasTable('nonexistent_table'))->toBeFalse();

        $builder->create('test_has_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        expect($builder->hasTable('test_has_table'))->toBeTrue();
    });

    test('hasView', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        expect($builder->hasView('nonexistent_view'))->toBeFalse();

        // Create a table
        $builder->create('test_has_view_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        // Create a view
        app()->db->connection('litebase')->statement('CREATE VIEW test_has_view AS SELECT * FROM test_has_view_table');

        expect($builder->hasView('test_has_view'))->toBeTrue();
    });

    test('pragma', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $result = $builder->pragma('foreign_keys');

        expect($result)->toBe(1);
    });

    test('rename', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_rename_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        expect($builder->rename('test_rename_table', 'renamed_table'))->toBeNull();
        expect($builder->hasTable('renamed_table'))->toBeTrue();
        expect($builder->hasTable('test_rename_table'))->toBeFalse();
    });

    test('table', function () {
        /** @var \Litebase\Laravel\Database\Schema\LitebaseBuilder $builder */
        $builder = app()->db->connection('litebase')->getSchemaBuilder();

        $builder->create('test_table_method', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $result = $builder->table('test_table_method', function (Blueprint $table) {
            $table->string('email');
        });

        expect($result)->toBeNull();

        $columns = $builder->getColumns('test_table_method');

        expect(collect($columns)->pluck('name')->all())
            ->toEqual(['id', 'name', 'created_at', 'updated_at', 'email']);
    });
});
