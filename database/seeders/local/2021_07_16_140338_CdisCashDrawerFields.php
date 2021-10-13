<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisCashDrawerFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $cashDrawer = [
            'type' => '2',
            'preset_name' => 'Cash Drawer (Default)',
            'data_entry' => 'cash_drawer',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($cashDrawer);

        $fieldMappingPresetDetail = [
            [
                'required' => 1,
                'field' => 'branch_code',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'branch_code',
            ],
            [
                'required' => 1,
                'field' => 'terminal_number',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'terminal_number',
            ],
            [
                'required' => 1,
                'field' => 'cashier_id',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'cashier_id',
            ],
            [
                'required' => 1,
                'field' => 'cashier_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'cashier_name',
            ],
            [
                'required' => 1,
                'field' => 'amount',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'amount',
            ],
            [
                'required' => 1,
                'field' => 'date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'date',
            ],
            [
                'required' => 1,
                'field' => 'approver_id',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'approver_id',
            ],
            [
                'required' => 1,
                'field' => 'approver_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'approver_name',
            ],
            [
                'required' => 1,
                'field' => 'approved_date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'approved_date',
            ],
            [
                'required' => 1,
                'field' => 'type',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'type',
            ],
            [
                'required' => 1,
                'field' => 'remarks',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'remarks',
            ],
        ];
        
        foreach ($fieldMappingPresetDetail as $value) {
            $fieldMappingPreset->detail()->create($value);
        }
    }

    /**
     * Reverts the database seeder.
     */
    public function down(): void
    {
        FieldMappingPreset::where([
            'preset_name' => 'Cash Drawer (Default)',
            'data_entry' => 'cash_drawer',
        ])->forceDelete();
    }
}
