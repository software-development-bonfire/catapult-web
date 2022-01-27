<?php

use App\Entities\FieldMappingPreset;
use App\Entities\FieldMappingPresetDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisPackagingVendorBranchCost extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $packagingVendorBranchCost = [
            'type' => '1',
            'preset_name' => 'Packaging Vendor Branch Cost (Default)',
            'data_entry' => 'packaging_vendor_branch_cost',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($packagingVendorBranchCost);

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
                'field' => 'packaging_vendor_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'packaging_vendor_bid',
            ],
            [
                'required' => 1,
                'field' => 'product_branch_availability_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'product_branch_availability_bid',
            ],
            [
                'required' => 1,
                'field' => 'cost',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'cost',
            ],
            [
                'required' => 1,
                'field' => 'price_to_branch',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'price_to_branch',
            ],
            [
                'required' => 1,
                'field' => 'price_to_branch_markup',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'price_to_branch_markup',
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
            'preset_name' => 'Packaging Vendor Branch Cost (Default)',
            'data_entry' => 'packaging_vendor_branch_cost',
        ])->forceDelete();
    }
}
