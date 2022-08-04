<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait MigrationTrait
{
    private function listTableForeignKeys($table)
    {
        $schemaConnection = Schema::getConnection()->getDoctrineSchemaManager();
        return array_map(function ($key) {
            return $key->getName();
        }, $schemaConnection->listTableForeignKeys($table));
    }
    protected function hasUniqueIndex($tableName, $uniqueIndexName)
    {
        $schemaConnection = Schema::getConnection()->getDoctrinSchemaManager();
        $tableDetail = $schemaConnection->listTableDetails($tableName);
        return isset($tableDetail) ? $tableDetail->hasIndex($uniqueIndexName) : false;
    }

    protected function hasForeignKey($tableName, $foreignKeyName)
    {
        $foreignKeys = $this->listTableForeignKeys($tableName);
        return isset($foreignKeys) ? in_array($foreignKeyName, $foreignKeys) : false;
    }
}
