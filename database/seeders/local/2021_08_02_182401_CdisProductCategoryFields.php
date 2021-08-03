<?php

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisProductCategoryFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $productPricingType = [
            'type' => '1',
            'api_version_name' => 'Product Category default',
            'api_endpoint' => 'Product Category',
            'status' => 1,
            'created_by' => 1,
        ];

        $field_map = FieldMapping::create($productPricingType);

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
                'field' => 'name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'name',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'button_color',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'button_color',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'status',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'status',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'parent_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'parent_bid',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'level',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'level',
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
