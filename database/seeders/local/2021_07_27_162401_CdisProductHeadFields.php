<?php

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductHeadFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $productHead = [
            'type' => '1',
            'api_version_name' => 'Product Head default',
            'api_endpoint' => 'Product Head',
            'status' => 1,
            'created_by' => 1,
        ];

        $field_map = FieldMapping::create($productHead);

        $field_mapping_details = [
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'bid',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'item_code',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'item_code',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'brand_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'brand_bid',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'status',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'status',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'is_sell_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_sell_item',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'is_inventory_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_inventory_item',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'is_finished_good',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_finished_good',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'tax_code',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'tax_code',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'is_senior_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_senior_item',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'is_pwd_item',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'is_pwd_item',
            ],
        ];

        foreach ($field_mapping_details as $value) {
            FieldMappingDetail::create($value);
        }
    }

    /**
     * Reverts the database seeder.
     */
    public function down(): void
    {
        // Remove your data
    }
}
