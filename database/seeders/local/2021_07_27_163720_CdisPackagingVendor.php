<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisPackagingVendor extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $packagingVendor = [
            'type' => '1',
            'preset_name' => 'Packaging Vendor (Default)',
            'data_entry' => 'packaging_vendor',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($packagingVendor);

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
                'field' => 'vendor_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'vendor_bid',
            ],
            [
                'required' => 1,
                'field' => 'product_uom_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'product_uom_bid',
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
            'preset_name' => 'Packaging Vendor (Default)',
            'data_entry' => 'packaging_vendor',
        ])->forceDelete();
    }
}
