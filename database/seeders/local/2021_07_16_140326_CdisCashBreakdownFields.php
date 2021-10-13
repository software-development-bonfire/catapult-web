<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisCashBreakdownFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $cashBreakdown = [
            'type' => '2',
            'preset_name' => 'Cash Breakdown (Default)',
            'data_entry' => 'cash_breakdown',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($cashBreakdown);

        $fieldMappingPresetDetail = [
            [
                'required' => 1,
                'field' => 'branch_code',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'CH',
                'default_value' => 0,
                'column_name' => 'branch_code',
            ],
            [
                'required' => 1,
                'field' => 'terminal_number',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'CH',
                'default_value' => '""',
                'column_name' => 'terminal_number',
            ],
            [
                'required' => 1,
                'field' => 'cashier_id',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'CH',
                'default_value' => 1,
                'column_name' => 'cashier_id',
            ],
            [
                'required' => 1,
                'field' => 'cashier_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'CH',
                'default_value' => '""',
                'column_name' => 'cashier_name',
            ],
            [
                'required' => 1,
                'field' => 'date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'CH',
                'default_value' => '""',
                'column_name' => 'date',
            ],
            [
                'required' => 1,
                'field' => 'approver_id',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'CH',
                'default_value' => 1,
                'column_name' => 'approver_id',
            ],
            [
                'required' => 1,
                'field' => 'approver_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'CH',
                'default_value' => 1,
                'column_name' => 'approver_name',
            ],
            [
                'required' => 1,
                'field' => 'approved_date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'CH',
                'default_value' => 1,
                'column_name' => 'approved_date',
            ],
            [
                'required' => 1,
                'field' => 'remarks',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'CH',
                'default_value' => 1,
                'column_name' => 'remarks',
            ],
            [
                'required' => 1,
                'field' => 'detail.*.denomination',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => 'CD',
                'default_value' => 0.00,
                'column_name' => 'denomination',
                'reference_column_name' => 'head_id',
                'head_reference' => 'CH.id',
            ],
            [
                'required' => 1,
                'field' => 'detail.*.quantity',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'CD',
                'default_value' => 1,
                'column_name' => 'quantity',
                'reference_column_name' => 'head_id',
                'head_reference' => 'CH.id',
            ],
            [
                'required' => 1,
                'field' => 'detail.*.amount',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => 'CD',
                'default_value' => 1,
                'column_name' => 'amount',
                'reference_column_name' => 'head_id',
                'head_reference' => 'CH.id',
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
            'preset_name' => 'Cash Breakdown (Default)',
            'data_entry' => 'cash_breakdown',
        ])->forceDelete();
    }
}
