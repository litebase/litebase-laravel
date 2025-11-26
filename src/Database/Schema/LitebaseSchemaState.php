<?php

namespace Litebase\Laravel\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\SchemaState;

class LitebaseSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param  string  $path
     * @return void
     */
    public function dump(Connection $connection, $path)
    {
        // Execute the schema query directly via the connection
        $schema = $this->connection->select(
            'SELECT sql FROM sqlite_master WHERE sql IS NOT NULL ORDER BY tbl_name, type DESC, name'
        );

        // Build the schema dump with proper formatting
        $schemaDump = collect($schema)
            ->map(fn($item) => is_array($item) ? $item['sql'] : $item->sql)
            ->filter(fn($sql) => ! preg_match('/CREATE TABLE sqlite_.+/i', $sql))
            ->map(fn($sql) => $sql . ';')
            ->implode(PHP_EOL . PHP_EOL);

        $this->files->put($path, $schemaDump . PHP_EOL);

        if ($this->hasMigrationTable()) {
            $this->appendMigrationData($path);
        }
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        $migrationTable = $this->getMigrationTable();

        // Get all migration records
        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $migrations */
        $migrations = $this->connection->table($migrationTable)->get();

        if ($migrations->isEmpty()) {
            return;
        }

        $insertStatements = [];

        foreach ($migrations as $migration) {

            $values = collect($migration)
                ->map(function ($value) {
                    if (is_null($value)) {
                        return 'NULL';
                    }

                    if (is_numeric($value)) {
                        return $value;
                    }

                    if (is_string($value)) {
                        return "'" . str_replace("'", "''", $value) . "'";
                    }

                    if (is_scalar($value)) {
                        return "'" . str_replace("'", "''", (string) $value) . "'";
                    }

                    return 'NULL';
                })
                ->values()
                ->implode(', ');

            $insertStatements[] = "INSERT INTO {$migrationTable} VALUES ({$values});";
        }

        $this->files->append($path, PHP_EOL . implode(PHP_EOL, $insertStatements) . PHP_EOL);
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $sql = $this->files->get($path);

        // Split the SQL file into individual statements
        $statements = $this->parseSqlStatements($sql);

        // Execute each statement
        foreach ($statements as $statement) {
            $statement = trim($statement);

            if (! empty($statement)) {
                $this->connection->statement($statement);
            }
        }
    }

    /**
     * Parse SQL statements from a file.
     *
     * @return array<int, string>
     */
    protected function parseSqlStatements(string $sql): array
    {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;

        // Split by semicolons
        $statements = explode(';', $sql);

        return array_filter(array_map('trim', $statements));
    }
}
