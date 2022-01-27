<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductVariantOptionFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $productVariant = [
            'type' => '1',
            'preset_name' => 'Product Variant Option (Default)',
            'data_entry' => 'product_variant_option',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($productVariant);

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
                'default_value' => 1,
                'column_name' => 'head_bid',
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
            'preset_name' => 'Product Variant Option (Default)',
            'data_entry' => 'product_variant_option',
        ])->forceDelete();
    }
}
