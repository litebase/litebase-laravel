<?php

namespace Tests\Integration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

describe('Laravel Migration Commands', function () {
    beforeEach(function () {
        // Clean up any leftover migration files from previous test runs
        $migrationPath = database_path('migrations');
        if (is_dir($migrationPath)) {
            $files = glob("{$migrationPath}/*.php");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Clean up migrations table before each test
        Schema::connection('litebase')->dropIfExists('migrations');

        // Clean up any test tables that might exist from previous runs
        $tables = [
            'test_users',
            'test_posts',
            'test_products',
            'step_table_1',
            'step_table_2',
            'step_table_3',
            'old_table_1',
            'fresh_test_table',
        ];

        foreach ($tables as $table) {
            Schema::connection('litebase')->dropIfExists($table);
        }
    });

    afterEach(function () {
        // Clean up any migration files created during the test
        $migrationPath = database_path('migrations');
        if (is_dir($migrationPath)) {
            $files = glob("{$migrationPath}/*.php");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Clean up after each test
        Schema::connection('litebase')->dropIfExists('migrations');

        $tables = [
            'test_users',
            'test_posts',
            'test_products',
            'step_table_1',
            'step_table_2',
            'step_table_3',
            'old_table_1',
            'fresh_test_table',
        ];

        foreach ($tables as $table) {
            Schema::connection('litebase')->dropIfExists($table);
        }
    });

    test('migrate command creates migrations table and runs migrations', function () {
        // Create a simple migration file for testing
        $migrationPath = database_path('migrations');

        if (! is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $migrationFile = "{$migrationPath}/{$timestamp}_create_test_users_table.php";

        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('test_users');
    }
};
PHP;

        file_put_contents($migrationFile, $migrationContent);

        try {
            // Run migrate command
            $exitCode = Artisan::call('migrate', [
                '--database' => 'litebase',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            expect($exitCode)->toBe(0);

            // Verify migrations table was created
            expect(Schema::connection('litebase')->hasTable('migrations'))->toBeTrue();

            // Verify test table was created
            expect(Schema::connection('litebase')->hasTable('test_users'))->toBeTrue();

            // Verify migration was recorded
            $connection = DB::connection('litebase');

            /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $migrations */
            $migrations = $connection->table('migrations')->get();

            expect($migrations)->not->toBeEmpty();
            expect($migrations[0]['migration'])->toContain('create_test_users_table');
        } finally {
            // Clean up migration file
            if (file_exists($migrationFile)) {
                unlink($migrationFile);
            }
        }
    });

    test('migrate:status command shows migration status', function () {
        // Create migrations table
        Schema::connection('litebase')->create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });

        $connection = DB::connection('litebase');
        $connection->table('migrations')->insert([
            'migration' => '2024_01_01_000000_create_test_table',
            'batch' => 1,
        ]);

        // Run migrate:status command
        $exitCode = Artisan::call('migrate:status', [
            '--database' => 'litebase',
        ]);

        expect($exitCode)->toBe(0);

        $output = Artisan::output();
        expect($output)->toContain('Migration name');
    });

    test('migrate --pretend shows SQL without executing', function () {
        $migrationPath = database_path('migrations');

        if (! is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His') . '1';
        $migrationFile = "{$migrationPath}/{$timestamp}_create_test_products_table.php";

        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('test_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('test_products');
    }
};
PHP;

        file_put_contents($migrationFile, $migrationContent);

        try {
            // Run migrate with --pretend flag
            $exitCode = Artisan::call('migrate', [
                '--database' => 'litebase',
                '--path' => 'database/migrations',
                '--pretend' => true,
                '--force' => true,
            ]);

            expect($exitCode)->toBe(0);

            // Verify table was NOT created (pretend mode)
            expect(Schema::connection('litebase')->hasTable('test_products'))->toBeFalse();

            $output = Artisan::output();
            // Output should contain SQL statements
            expect($output)->toContain('create');
        } finally {
            if (file_exists($migrationFile)) {
                unlink($migrationFile);
            }
        }
    });

    test('migrate:rollback rolls back last batch of migrations', function () {
        // First run a migration to have something to rollback
        $migrationPath = database_path('migrations');

        if (! is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $migrationFile = "{$migrationPath}/2024_01_01_000000_create_test_users_rollback.php";

        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('test_users');
    }
};
PHP;

        file_put_contents($migrationFile, $migrationContent);

        try {
            // Run migration first
            Artisan::call('migrate', [
                '--database' => 'litebase',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            expect(Schema::connection('litebase')->hasTable('test_users'))->toBeTrue();

            // Run rollback command
            $exitCode = Artisan::call('migrate:rollback', [
                '--database' => 'litebase',
                '--force' => true,
            ]);

            // Verify rollback executed successfully (even if nothing to rollback)
            expect($exitCode)->toBe(0);
        } finally {
            if (file_exists($migrationFile)) {
                unlink($migrationFile);
            }

            Schema::connection('litebase')->dropIfExists('test_users');
        }
    });

    test('migrate:rollback with --step option rolls back specific number of migrations', function () {
        $migrationPath = database_path('migrations');

        if (! is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        // Create three migration files
        $migration1 = "{$migrationPath}/2024_01_01_000000_first_step_migration.php";
        $migration2 = "{$migrationPath}/2024_01_02_000000_second_step_migration.php";
        $migration3 = "{$migrationPath}/2024_01_03_000000_third_step_migration.php";

        $content1 = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('step_table_1', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('step_table_1');
    }
};
PHP;

        $content2 = str_replace('step_table_1', 'step_table_2', $content1);
        $content3 = str_replace('step_table_1', 'step_table_3', $content1);

        file_put_contents($migration1, $content1);
        file_put_contents($migration2, $content2);
        file_put_contents($migration3, $content3);

        try {
            // Run all migrations
            Artisan::call('migrate', [
                '--database' => 'litebase',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            $connection = DB::connection('litebase');
            $allMigrations = $connection->table('migrations')->where('migration', 'like', '%step_migration%')->get();
            $initialCount = $allMigrations->count();
            expect($initialCount)->toBeGreaterThanOrEqual(3);

            // Rollback only 1 migration
            $exitCode = Artisan::call('migrate:rollback', [
                '--database' => 'litebase',
                '--step' => 1,
                '--force' => true,
            ]);

            expect($exitCode)->toBe(0);

            // Verify only one migration was rolled back
            $remainingMigrations = $connection->table('migrations')->where('migration', 'like', '%step_migration%')->get();
            expect($remainingMigrations->count())->toBe($initialCount - 1);
        } finally {
            if (file_exists($migration1)) {
                unlink($migration1);
            }

            if (file_exists($migration2)) {
                unlink($migration2);
            }

            if (file_exists($migration3)) {
                unlink($migration3);
            }

            Schema::connection('litebase')->dropIfExists('step_table_1');
            Schema::connection('litebase')->dropIfExists('step_table_2');
            Schema::connection('litebase')->dropIfExists('step_table_3');
        }
    });

    test('migrate:reset rolls back all migrations', function () {
        Schema::connection('litebase')->create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });

        Schema::connection('litebase')->create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::connection('litebase')->create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });

        $connection = DB::connection('litebase');
        $connection->table('migrations')->insert([
            ['migration' => '2024_01_01_000000_create_test_users_table', 'batch' => 1],
            ['migration' => '2024_01_02_000000_create_test_posts_table', 'batch' => 1],
        ]);

        // Create migration files
        $migrationPath = database_path('migrations');

        if (! is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $migration1 = "{$migrationPath}/2024_01_01_000000_create_test_users_table.php";
        $migration2 = "{$migrationPath}/2024_01_02_000000_create_test_posts_table.php";

        $content1 = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('test_users');
    }
};
PHP;

        $content2 = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('test_posts');
    }
};
PHP;

        file_put_contents($migration1, $content1);
        file_put_contents($migration2, $content2);

        try {
            $exitCode = Artisan::call('migrate:reset', [
                '--database' => 'litebase',
                '--force' => true,
            ]);

            expect($exitCode)->toBe(0);

            // Verify all tables were dropped
            expect(Schema::connection('litebase')->hasTable('test_users'))->toBeFalse();
            expect(Schema::connection('litebase')->hasTable('test_posts'))->toBeFalse();

            // Verify migrations table is empty
            $migrations = $connection->table('migrations')->get();
            expect($migrations)->toBeEmpty();
        } finally {
            if (file_exists($migration1)) {
                unlink($migration1);
            }
            if (file_exists($migration2)) {
                unlink($migration2);
            }
        }
    });

    test('migrate:refresh resets and re-runs all migrations', function () {
        $migrationPath = database_path('migrations');
        if (! is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His') . '2';
        $migrationFile = "{$migrationPath}/{$timestamp}_create_test_users_refresh.php";

        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('test_users');
    }
};
PHP;

        file_put_contents($migrationFile, $migrationContent);

        try {
            // First run migrations
            Artisan::call('migrate', [
                '--database' => 'litebase',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            expect(Schema::connection('litebase')->hasTable('test_users'))->toBeTrue();

            // Run refresh
            $exitCode = Artisan::call('migrate:refresh', [
                '--database' => 'litebase',
                '--force' => true,
            ]);

            expect($exitCode)->toBe(0);

            // Verify table still exists (was re-created)
            expect(Schema::connection('litebase')->hasTable('test_users'))->toBeTrue();
        } finally {
            if (file_exists($migrationFile)) {
                unlink($migrationFile);
            }
        }
    });

    test('migrate:fresh drops all tables and re-runs migrations', function () {
        // Create some tables in the litebase database
        Schema::connection('litebase')->create('old_table_1', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        expect(Schema::connection('litebase')->hasTable('old_table_1'))->toBeTrue();

        // Create a migration file
        $migrationPath = database_path('migrations');
        if (! is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His') . '3';
        $migrationFile = "{$migrationPath}/{$timestamp}_create_fresh_test_table.php";

        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('litebase')->create('fresh_test_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::connection('litebase')->dropIfExists('fresh_test_table');
    }
};
PHP;

        file_put_contents($migrationFile, $migrationContent);

        try {
            // Run fresh (drops all tables and runs migrations)
            $exitCode = Artisan::call('migrate:fresh', [
                '--database' => 'litebase',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            expect($exitCode)->toBe(0);

            // Verify new migration was run - migrations table should exist
            expect(Schema::connection('litebase')->hasTable('migrations'))->toBeTrue();

            // Verify fresh_test_table was created
            expect(Schema::connection('litebase')->hasTable('fresh_test_table'))->toBeTrue();
        } finally {
            if (file_exists($migrationFile)) {
                unlink($migrationFile);
            }

            Schema::connection('litebase')->dropIfExists('fresh_test_table');
            Schema::connection('litebase')->dropIfExists('old_table_1');
        }
    });
});
