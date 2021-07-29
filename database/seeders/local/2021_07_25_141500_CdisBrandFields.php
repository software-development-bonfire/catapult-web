<?php

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisBrandFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $brand = [
            'type' => '1',
            'api_version_name' => 'Brand default',
            'api_endpoint' => 'Brand',
            'status' => 1,
            'created_by' => 1,
        ];

        $field_map = FieldMapping::create($brand);

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
                'field' => 'code',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'code',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'name',
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
