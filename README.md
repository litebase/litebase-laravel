# Litebase Laravel SDK (Alpha)

[![tests](https://github.com/litebase/litebase-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/litebase/litebase-laravel/actions/workflows/tests.yml)

A Laravel database driver for [Litebase](https://github.com/litebase/litebase), an open source distributed database built on SQLite, distributed file systems, and object storage.

## Installation

You can install the package via composer:

```bash
composer require litebase/litebase-laravel
```

The service provider will be automatically registered.

## Configuration

Add a Litebase connection to your `config/database.php`:

```php
'connections' => [
    // ... other connections
    
    'litebase' => [
        'driver' => 'litebase',
        'database' => env('LITEBASE_DATABASE', 'your_database/main'),
        'host' => env('LITEBASE_HOST', 'localhost'),
        'port' => env('LITEBASE_PORT', '8888'),
        'access_key_id' => env('LITEBASE_ACCESS_KEY_ID'),
        'access_key_secret' => env('LITEBASE_ACCESS_KEY_SECRET'),
    ],
],
```

Add the corresponding environment variables to your `.env`:

```env
LITEBASE_HOST=localhost
LITEBASE_PORT=8888
LITEBASE_ACCESS_KEY_ID=lbakid_**********
LITEBASE_ACCESS_KEY_SECRET=lbaks_**********
LITEBASE_DATABASE=your_database/main
```

## Usage

Once configured, you can use Litebase like any other Laravel database connection:

### Query Builder

```php
use Illuminate\Support\Facades\DB;

// Select
$users = DB::connection('litebase')
    ->table('users')
    ->where('active', true)
    ->get();

// Insert
DB::connection('litebase')
    ->table('users')
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

// Update
DB::connection('litebase')
    ->table('users')
    ->where('id', 1)
    ->update(['name' => 'Jane Doe']);

// Delete
DB::connection('litebase')
    ->table('users')
    ->where('id', 1)
    ->delete();
```

### Eloquent Models

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $connection = 'litebase';
    protected $table = 'users';
}

// Use the model
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

$users = User::where('active', true)->get();
```

### Migrations

Use Laravel's migration system as usual:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'litebase';
    
    public function up()
    {
        Schema::connection('litebase')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::connection('litebase')->dropIfExists('users');
    }
};
```

Run migrations:

```bash
php artisan migrate --database=litebase
```

### Schema Operations

```php
use Illuminate\Support\Facades\Schema;

// Check if table exists
if (Schema::connection('litebase')->hasTable('users')) {
    // ...
}

// Get all tables
$tables = Schema::connection('litebase')->getTables();

// Get table columns
$columns = Schema::connection('litebase')->getColumns('users');
```

### Transactions

```php
use Illuminate\Support\Facades\DB;

DB::connection('litebase')->transaction(function () {
    DB::connection('litebase')
        ->table('users')
        ->insert(['name' => 'John Doe', 'email' => 'john@example.com']);
    
    DB::connection('litebase')
        ->table('logs')
        ->insert(['action' => 'user_created']);
});
```

### Interactive Database Shell

The package includes an interactive database shell command:

```bash
php artisan litebase:db [connection?]
```

This provides an interactive SQL prompt where you can execute queries directly against your Litebase database.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Testing

Run unit tests:

```sh
composer test
```

Run integration tests (requires Docker):

```sh
composer test-integration
```

*Integration tests require a running Litebase Server. When running integration tests, a server will be automatically started using Docker.*

Run static analysis:

```sh
composer phpstan
```

Run code style checks:

```sh
composer pint
```

## Code of Conduct

Please see [Code of Conduct](https://github.com/litebase/litebase-laravel?tab=coc-ov-file) for details.

## Security

All security related issues should be reported directly to [security@litebase.com](mailto:security@litebase.com).

## License

Litebase is [open-sourced](https://opensource.org/) software licensed under the [MIT License](LICENSE.md).
