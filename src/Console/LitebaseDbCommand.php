<?php

namespace Litebase\Laravel\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Interactive Litebase Database Command
 */
#[AsCommand(name: 'litebase:db')]
class LitebaseDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'litebase:db {connection? : The Litebase connection that should be used}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start an interactive Litebase database session';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connectionName = $this->argument('connection')
            ?? $this->laravel['config']['database.default'];

        $connection = $this->laravel['db']->connection($connectionName);

        if (!$connection instanceof \Litebase\Laravel\LitebaseConnection) {
            $this->components->error(
                'The specified connection is not a Litebase connection.'
            );

            return Command::FAILURE;
        }

        $this->info("Litebase Interactive Session");
        $this->info("Connection: {$connectionName}");
        $this->info("Database: {$connection->getDatabaseName()}");
        $this->info("Server Version: {$connection->getServerVersion()}");
        $this->newLine();
        $this->comment("Type SQL queries (end with semicolon) or 'exit' to quit.");
        $this->newLine();

        $query = '';

        while (true) {
            $prompt = empty($query) ? 'litebase> ' : '       -> ';
            $line = $this->ask($prompt);

            if ($line === null || trim(strtolower($line)) === 'exit' || trim(strtolower($line)) === 'quit') {
                $this->info('Goodbye!');
                break;
            }

            $query .= ' ' . $line;

            // Check if query ends with semicolon
            if (str_ends_with(trim($query), ';')) {
                $query = trim($query);

                try {
                    // Remove the trailing semicolon
                    $sql = rtrim($query, ';');

                    // Determine query type
                    if (preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)/i', $sql)) {
                        $results = $connection->select($sql);

                        if (empty($results)) {
                            $this->info('(0 rows)');
                        } else {
                            $this->displayResults($results);
                        }
                    } else {
                        $affectedRows = $connection->affectingStatement($sql);
                        $this->info("Query OK, {$affectedRows} row(s) affected");
                    }
                } catch (\Exception $e) {
                    $this->error('Error: ' . $e->getMessage());
                }

                $query = '';
                $this->newLine();
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Display query results in a table format.
     *
     * @param  array  $results
     * @return void
     */
    protected function displayResults(array $results)
    {
        if (empty($results)) {
            return;
        }

        // Convert objects/arrays to arrays
        $rows = array_map(fn ($row) => is_array($row) ? $row : (array) $row, $results);

        // Get headers from first row
        $headers = array_keys($rows[0]);

        $this->table($headers, $rows);
        $this->info('(' . count($rows) . ' row' . (count($rows) !== 1 ? 's' : '') . ')');
    }
}
