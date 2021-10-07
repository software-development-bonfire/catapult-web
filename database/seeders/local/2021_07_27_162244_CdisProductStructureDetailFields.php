<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductStructureDetailFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $productStructureDetail = [
            'type' => '1',
            'preset_name' => 'Product Structure Detail (Default)',
            'data_entry' => 'product_structure_detail',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($productStructureDetail);

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
                'field' => 'head_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'head_bid',
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
                'field' => 'quantity',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => '0.000000',
                'column_name' => 'quantity',
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
            'preset_name' => 'Product Structure Detail (Default)',
            'data_entry' => 'product_structure_detail',
        ])->forceDelete();
    }
}
