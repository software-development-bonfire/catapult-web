<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductUOMPackaging extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $productUOMPackaging = [
            'type' => '1',
            'preset_name' => 'Product UOM Packaging (Default)',
            'data_entry' => 'product_uom_packaging',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($productUOMPackaging);

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
                'field' => 'barcode',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'barcode',
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
                'field' => 'long_description',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'long_description',
            ],
            [
                'required' => 1,
                'field' => 'product_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'product_bid',
            ],
            [
                'required' => 1,
                'field' => 'uom_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'uom_bid',
            ],
            [
                'required' => 1,
                'field' => 'variant_option',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'variant_option',
            ],
            [
                'required' => 1,
                'field' => 'pack_content',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'pack_content',
            ],
            [
                'required' => 1,
                'field' => 'is_raw_material',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_raw_material',
            ],
            [
                'required' => 1,
                'field' => 'is_addon',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_addon',
            ],
            [
                'required' => 1,
                'field' => 'is_sell_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'is_sell_item',
            ],
            [
                'required' => 1,
                'field' => 'is_inventory_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'is_inventory_item',
            ],
            [
                'required' => 1,
                'field' => 'is_senior_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_senior_item',
            ],
            [
                'required' => 1,
                'field' => 'is_pwd_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_pwd_item',
            ],
            [
                'required' => 1,
                'field' => 'is_default',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_default',
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
            'preset_name' => 'Product UOM Packaging (Default)',
            'data_entry' => 'product_uom_packaging',
        ])->forceDelete();
    }
}
