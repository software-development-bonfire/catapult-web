<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisAuditTrailFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $auditTrail = [
            'type' => '2',
            'preset_name' => 'Audit Trail (Default)',
            'data_entry' => 'audit_trail',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($auditTrail);

        $fieldMappingPresetDetail = [
            [
                'required' => 0,
                'field' => 'branch_code',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'AT',
                'default_value' => 0,
                'column_name' => 'branch_code',
            ],
            [
                'required' => 0,
                'field' => 'terminal_no',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'terminal_no',
            ],
            [
                'required' => 0,
                'field' => 'date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'date',
            ],
            [
                'required' => 0,
                'field' => 'application',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'application',
            ],
            [
                'required' => 0,
                'field' => 'cashier',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'cashier',
            ],
            [
                'required' => 0,
                'field' => 'supervisor',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'supervisor',
            ],
            [
                'required' => 0,
                'field' => 'job',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'job',
            ],
            [
                'required' => 0,
                'field' => 'transaction_no',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'AT',
                'default_value' => 1,
                'column_name' => 'transaction_no',
            ],
            [
                'required' => 0,
                'field' => 'receipt_no',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'AT',
                'default_value' => 1,
                'column_name' => 'receipt_no',
            ],
            [
                'required' => 0,
                'field' => 'remarks',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
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
            'preset_name' => 'Audit Trail (Default)',
            'data_entry' => 'audit_trail',
        ])->forceDelete();
    }
}
