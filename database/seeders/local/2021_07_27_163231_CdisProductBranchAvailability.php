<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductBranchAvailability extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $productBranchAvailability = [
            'type' => '1',
            'preset_name' => 'Product Branch Availability (Default)',
            'data_entry' => 'product_branch_availability',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($productBranchAvailability);

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
                'field' => 'branch_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'branch_bid',
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
                'field' => 'is_available',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_available',
            ],
            [
                'required' => 1,
                'field' => 'max_stock',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => '0.000000',
                'column_name' => 'max_stock',
            ],
            [
                'required' => 1,
                'field' => 'min_stock',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => '0.000000',
                'column_name' => 'min_stock',
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
            'preset_name' => 'Product Branch Availability (Default)',
            'data_entry' => 'product_branch_availability',
        ])->forceDelete();
    }
}
