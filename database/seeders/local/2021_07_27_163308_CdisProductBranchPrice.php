<?php

use App\Entities\FieldMappingPreset;
use App\Entities\FieldMappingPresetDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductBranchPrice extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $productBranchPrice = [
            'type' => '1',
            'preset_name' => 'Product Branch Price (Default)',
            'data_entry' => 'product_branch_price',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($productBranchPrice);

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
                'field' => 'product_branch_availability_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'product_branch_availability_bid',
            ],
            [
                'required' => 1,
                'field' => 'product_pricing_type_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'product_pricing_type_bid',
            ],
            [
                'required' => 1,
                'field' => 'price',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'price',
            ],
            [
                'required' => 1,
                'field' => 'markup',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'markup',
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
            'preset_name' => 'Product Branch Price (Default)',
            'data_entry' => 'product_branch_price',
        ])->forceDelete();
    }
}
