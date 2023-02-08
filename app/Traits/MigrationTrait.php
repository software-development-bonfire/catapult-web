<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
        $schemaConnection = Schema::getConnection()->getDoctrineSchemaManager();
        $tableDetail = $schemaConnection->listTableDetails($tableName);
        $isIndexExist = isset($tableDetail) ? $tableDetail->hasIndex($uniqueIndexName) : false;
        if (! $isIndexExist) {
            $indexesFound = $schemaConnection->listTableIndexes($tableName);
            return array_key_exists($uniqueIndexName, $indexesFound);
        }
        $indexes = DB::select("SHOW INDEXES FROM ".$tableName);
        return collect($indexes)->pluck('Key_name')->contains($uniqueIndexName);
    }

    protected function hasForeignKey($tableName, $foreignKeyName)
    {
        $foreignKeys = $this->listTableForeignKeys($tableName);
        
        return isset($foreignKeys) ? in_array($foreignKeyName, $foreignKeys) : false;
    }

    protected function isReferencedColumn($tableName, $columnName)
    {
        $isReference = false;
        $databaseName = config()->get('database.connections.mysql.database');
        $details = DB::select("SELECT
                    TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                FROM
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE
                    REFERENCED_TABLE_SCHEMA = '".$databaseName."' AND
                    TABLE_NAME = '".$tableName."' AND
                    COLUMN_NAME ='".$columnName."';");


        if (! empty($details)) {
            if (isset($details[0])) {
                $detail = (object)$details[0];
                if (! empty($detail->REFERENCED_TABLE_NAME) &&  ! empty($detail->REFERENCED_COLUMN_NAME)) {
                    $isReference = true;
                }
            }
        }
        return $isReference;
    }

    protected function hasValidIndentifierLimit($indexName)
    {
        $charLength = Str::length($indexName);
        return (intval($charLength) < 64);
    }

    protected function expectedIndexUniqueForeignKeyName($tableName, $columnName, $postFix)
    {
        return $tableName.'_'.$columnName.'_'.$postFix;
    }

    protected function customizedIndexUniqueForeignKeyName($tableName, $columnName, $postFix)
    {
        return $this->extractTableInitial($tableName).'_'.$columnName.'_'.$postFix;
    }

    protected function extractTableInitial($tableName)
    {
        $nameParts = explode('_', trim($tableName));
        $initials = [];
        foreach ($nameParts as $part) {
            $initials[] = substr($part, 0, 1);
        }
        $tableInitial = implode('', $initials);
        return trim($tableInitial);
    }
}
