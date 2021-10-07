<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisVendorFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $vendor = [
            'type' => '1',
            'preset_name' => 'Vendor (Default)',
            'data_entry' => 'vendor',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($vendor);

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
                'field' => 'description',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'description',
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
                'field' => 'payment_term_days',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'payment_term_days',
            ],
            [
                'required' => 1,
                'field' => 'tin_no',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'tin_no',
            ],
            [
                'required' => 1,
                'field' => 'email',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'email',
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
                'field' => 'status',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'status',
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
            'preset_name' => 'Vendor (Default)',
            'data_entry' => 'vendor',
        ])->forceDelete();
    }
}
