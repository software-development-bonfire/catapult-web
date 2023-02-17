<?php

use App\Entities\FieldMappingPreset;
use App\Entities\FieldMappingPresetDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $product = [
            'type' => '1',
            'preset_name' => 'Product (Default)',
            'data_entry' => 'product',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($product);

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
                'field' => 'item_code',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'item_code',
            ],
            [
                'required' => 1,
                'field' => 'category_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'category_bid',
            ],
            [
                'required' => 1,
                'field' => 'brand_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'brand_bid',
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
                'field' => 'is_finished_good',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_finished_good',
            ],
            [
                'required' => 1,
                'field' => 'tax_code',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'tax_code',
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
            'preset_name' => 'Product (Default)',
            'data_entry' => 'product',
        ])->forceDelete();
    }
}
