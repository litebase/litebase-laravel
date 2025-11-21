<?php

namespace Tests\Integration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Litebase\ApiClient;
use Litebase\Configuration;
use Litebase\OpenAPI\Model\DatabaseStoreRequest;

beforeAll(function () {
    $configuration = new Configuration;

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

describe('Litebase Migrations', function () {
    beforeEach(function () {
        // Set connection for each test
        Schema::connection('litebase');
    });

    afterEach(function () {
        // Clean up tables after each test
        $tables = ['test_table', 'users', 'posts', 'flights', 'products', 'orders'];

        foreach ($tables as $table) {
            Schema::connection('litebase')->dropIfExists($table);
        }
    });

    describe('Table Operations', function () {
        test('can create table', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });

            expect(Schema::connection('litebase')->hasTable('test_table'))->toBeTrue();
        });

        test('can determine table existence', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email');
            });

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();
            expect(Schema::connection('litebase')->hasTable('nonexistent'))->toBeFalse();
        });

        test('can determine column existence', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->string('name');
            });

            expect(Schema::connection('litebase')->hasColumn('users', 'email'))->toBeTrue();
            expect(Schema::connection('litebase')->hasColumn('users', 'name'))->toBeTrue();
            expect(Schema::connection('litebase')->hasColumn('users', 'nonexistent'))->toBeFalse();
        });

        test('can update table by adding columns', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->table('users', function (Blueprint $table) {
                $table->string('email');
                $table->integer('votes')->default(0);
            });

            expect(Schema::connection('litebase')->hasColumn('users', 'email'))->toBeTrue();
            expect(Schema::connection('litebase')->hasColumn('users', 'votes'))->toBeTrue();
        });

        test('can rename table', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->rename('users', 'customers');

            expect(Schema::connection('litebase')->hasTable('customers'))->toBeTrue();
            expect(Schema::connection('litebase')->hasTable('users'))->toBeFalse();

            Schema::connection('litebase')->dropIfExists('customers');
        });

        test('can drop table', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();

            Schema::connection('litebase')->drop('users');

            expect(Schema::connection('litebase')->hasTable('users'))->toBeFalse();
        });

        test('can drop table if exists', function () {
            Schema::connection('litebase')->dropIfExists('nonexistent');

            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
            });

            Schema::connection('litebase')->dropIfExists('users');

            expect(Schema::connection('litebase')->hasTable('users'))->toBeFalse();
        });
    });

    describe('Column Types', function () {
        test('supports basic column types', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                // Numeric types
                $table->id();
                $table->bigInteger('big_int');
                $table->integer('int');
                $table->smallInteger('small_int');
                $table->tinyInteger('tiny_int');
                $table->decimal('amount', 8, 2);
                $table->float('float_val');
                $table->double('double_val');

                // String types
                $table->string('name', 100);
                $table->text('description');
                $table->char('code', 10);

                // Date/Time types
                $table->date('birth_date');
                $table->time('start_time');
                $table->dateTime('created_at');
                $table->timestamp('updated_at');

                // Boolean
                $table->boolean('is_active');

                // JSON
                $table->json('metadata');

                // Binary
                $table->binary('photo');
            });

            expect(Schema::connection('litebase')->hasTable('test_table'))->toBeTrue();

            $columns = Schema::connection('litebase')->getColumnListing('test_table');
            expect($columns)->toContain('id');
            expect($columns)->toContain('name');
            expect($columns)->toContain('description');
            expect($columns)->toContain('amount');
            expect($columns)->toContain('is_active');
            expect($columns)->toContain('metadata');
        });

        test('supports unsigned integer types', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedInteger('count');
                $table->unsignedSmallInteger('status');
                $table->unsignedTinyInteger('type');
            });

            $columns = Schema::connection('litebase')->getColumnListing('test_table');
            expect($columns)->toContain('user_id');
            expect($columns)->toContain('count');
        });

        test('supports increment types', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
            });

            Schema::connection('litebase')->create('products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
            });

            expect(Schema::connection('litebase')->hasTable('test_table'))->toBeTrue();
            expect(Schema::connection('litebase')->hasTable('products'))->toBeTrue();
        });

        test('supports timestamps and soft deletes', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });

            $columns = Schema::connection('litebase')->getColumnListing('test_table');
            expect($columns)->toContain('created_at');
            expect($columns)->toContain('updated_at');
            expect($columns)->toContain('deleted_at');
        });

        test('supports uuid and ulid types', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->uuid('uuid');
                $table->ulid('ulid');
            });

            $columns = Schema::connection('litebase')->getColumnListing('test_table');
            expect($columns)->toContain('uuid');
            expect($columns)->toContain('ulid');
        });

        test('supports foreignId column type', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('title');
            });

            expect(Schema::connection('litebase')->hasColumn('posts', 'user_id'))->toBeTrue();
        });
    });

    describe('Column Modifiers', function () {
        test('supports nullable modifier', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('email')->nullable();
                $table->string('name');
            });

            expect(Schema::connection('litebase')->hasTable('test_table'))->toBeTrue();
        });

        test('supports default modifier', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('pending');
                $table->integer('votes')->default(0);
                $table->boolean('is_active')->default(true);
            });

            expect(Schema::connection('litebase')->hasTable('test_table'))->toBeTrue();
        });

        test('supports unsigned modifier', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->integer('count')->unsigned();
            });

            expect(Schema::connection('litebase')->hasTable('test_table'))->toBeTrue();
        });

        test('supports comment modifier', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('email')->comment('User email address');
            });

            expect(Schema::connection('litebase')->hasTable('test_table'))->toBeTrue();
        });
    });

    describe('Column Operations', function () {
        test('can rename column', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->table('test_table', function (Blueprint $table) {
                $table->renameColumn('name', 'full_name');
            });

            expect(Schema::connection('litebase')->hasColumn('test_table', 'full_name'))->toBeTrue();
            expect(Schema::connection('litebase')->hasColumn('test_table', 'name'))->toBeFalse();
        });

        test('can drop column', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->integer('votes');
            });

            Schema::connection('litebase')->table('test_table', function (Blueprint $table) {
                $table->dropColumn('votes');
            });

            expect(Schema::connection('litebase')->hasColumn('test_table', 'votes'))->toBeFalse();
            expect(Schema::connection('litebase')->hasColumn('test_table', 'name'))->toBeTrue();
        });

        test('can drop multiple columns', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->integer('votes');
                $table->string('avatar');
            });

            Schema::connection('litebase')->table('test_table', function (Blueprint $table) {
                $table->dropColumn(['votes', 'avatar']);
            });

            expect(Schema::connection('litebase')->hasColumn('test_table', 'votes'))->toBeFalse();
            expect(Schema::connection('litebase')->hasColumn('test_table', 'avatar'))->toBeFalse();
            expect(Schema::connection('litebase')->hasColumn('test_table', 'name'))->toBeTrue();
        });

        test('can drop timestamps', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });

            Schema::connection('litebase')->table('test_table', function (Blueprint $table) {
                $table->dropTimestamps();
            });

            expect(Schema::connection('litebase')->hasColumn('test_table', 'created_at'))->toBeFalse();
            expect(Schema::connection('litebase')->hasColumn('test_table', 'updated_at'))->toBeFalse();
        });

        test('can drop soft deletes', function () {
            Schema::connection('litebase')->create('test_table', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->softDeletes();
            });

            Schema::connection('litebase')->table('test_table', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });

            expect(Schema::connection('litebase')->hasColumn('test_table', 'deleted_at'))->toBeFalse();
        });
    });

    describe('Indexes', function () {
        test('can create unique index', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
            });

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();
        });

        test('can create basic index', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->string('state');

                $table->index('state');
            });

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();
        });

        test('can create compound index', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name');

                $table->index(['first_name', 'last_name']);
            });

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();
        });

        test('can create primary key', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->string('email');
                $table->primary('email');
            });

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();
        });

        test('can drop index', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('state');
                $table->index('state');
            });

            $indexes = Schema::connection('litebase')->getIndexes('users');
            expect($indexes)->not->toBeEmpty();

            Schema::connection('litebase')->table('users', function (Blueprint $table) {
                $table->dropIndex(['state']);
            });

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();
        });
    });

    describe('Foreign Key Constraints', function () {
        test('can create foreign key constraint', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->create('posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title');

                $table->foreign('user_id')->references('id')->on('users');
            });

            expect(Schema::connection('litebase')->hasTable('posts'))->toBeTrue();
        });

        test('can create foreign key with foreignId shorthand', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->string('title');
            });

            expect(Schema::connection('litebase')->hasTable('posts'))->toBeTrue();
            expect(Schema::connection('litebase')->hasColumn('posts', 'user_id'))->toBeTrue();
        });

        test('can create foreign key with cascade actions', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->string('title');
            });

            expect(Schema::connection('litebase')->hasTable('posts'))->toBeTrue();
        });

        test('can drop foreign key', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection('litebase')->create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->string('title');
            });

            Schema::connection('litebase')->table('posts', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            expect(Schema::connection('litebase')->hasTable('posts'))->toBeTrue();
        });

        test('can enable and disable foreign key constraints', function () {
            Schema::connection('litebase')->disableForeignKeyConstraints();

            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
            });

            Schema::connection('litebase')->enableForeignKeyConstraints();

            expect(Schema::connection('litebase')->hasTable('users'))->toBeTrue();
        });
    });

    describe('Special Column Types', function () {
        test('supports morphs columns', function () {
            Schema::connection('litebase')->create('taggables', function (Blueprint $table) {
                $table->id();
                $table->morphs('taggable');
            });

            $columns = Schema::connection('litebase')->getColumnListing('taggables');
            expect($columns)->toContain('taggable_id');
            expect($columns)->toContain('taggable_type');

            Schema::connection('litebase')->dropIfExists('taggables');
        });

        test('supports nullable morphs columns', function () {
            Schema::connection('litebase')->create('taggables', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('taggable');
            });

            $columns = Schema::connection('litebase')->getColumnListing('taggables');
            expect($columns)->toContain('taggable_id');
            expect($columns)->toContain('taggable_type');

            Schema::connection('litebase')->dropIfExists('taggables');
        });

        test('supports remember token column', function () {
            Schema::connection('litebase')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->rememberToken();
            });

            $columns = Schema::connection('litebase')->getColumnListing('users');
            expect($columns)->toContain('remember_token');
        });
    });
});
