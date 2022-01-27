<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisBranchFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $branch = [
            'type' => '1',
            'preset_name' => 'Branch (Default)',
            'data_entry' => 'branch',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($branch);

        $fieldMappingPresetDetail = [
            [
                'required' => 1,
                'field' => 'bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'bid',
            ],
            [
                'required' => 1,
                'field' => 'code',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'code',
            ],
            [
                'required' => 1,
                'field' => 'name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'name',
            ],
            [
                'required' => 1,
                'field' => 'address',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'address',
            ],
            [
                'required' => 1,
                'field' => 'contact_person',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'contact_person',
            ],
            [
                'required' => 1,
                'field' => 'contact_number',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'contact_number',
            ],
            [
                'required' => 1,
                'field' => 'business_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'business_name',
            ],
            [
                'required' => 1,
                'field' => 'type',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'type',
            ],
            [
                'required' => 1,
                'field' => 'tin_no',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'tin_no',
            ],
            [
                'required' => 1,
                'field' => 'status',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'status',
            ],
            [
                'required' => 1,
                'field' => 'is_main_branch',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_main_branch',
            ],
            [
                'required' => 1,
                'field' => 'start_operation_hour',
                'description' => null,
                'mapping_type' => 'TIME',
                'file_name' => '""',
                'default_value' => '00:00:00',
                'column_name' => 'start_operation_hour',
            ],
            [
                'required' => 1,
                'field' => 'end_operation_hour',
                'description' => null,
                'mapping_type' => 'TIME',
                'file_name' => '""',
                'default_value' => '23:59:59',
                'column_name' => 'end_operation_hour',
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
            'preset_name' => 'Branch (Default)',
            'data_entry' => 'branch',
        ])->forceDelete();
    }
}
