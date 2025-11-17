<?php

namespace Litebase\Laravel\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\SQLiteGrammar;

class LitebaseGrammar extends SQLiteGrammar
{
    /**
     * @inheritDoc
     */
    public function compileDropAllTables($schema = null)
    {
        $tables = $this->connection->select("select name from {$this->wrap($schema ?? 'main')}.sqlite_master where type = 'table' and name not like 'sqlite_%'");

        return collect($tables)->map(function ($table) use ($schema) {
            return 'drop table ' . $this->wrapTable($table['name']);
        })->implode('; ') . ';';
    }

    public function compileRebuild($schema = null)
    {
        return sprintf(
            'vacuum %s',
            $this->wrapValue($schema ?? 'main')
        );
    }
}
