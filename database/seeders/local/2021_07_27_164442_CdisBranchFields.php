<?php

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisBranchFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $branch = [
            'type' => '1',
            'api_version_name' => 'Branch default',
            'api_endpoint' => 'Branch',
            'status' => 1,
            'created_by' => 1,
        ];

        $field_map = FieldMapping::create($branch);

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
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'code',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'name',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'address',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'address',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'contact_person',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'contact_person',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'contact_number',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'contact_number',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'business_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'business_name',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'type',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'type',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'tin_no',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'tin_no',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'status',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'status',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'is_main_branch',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'is_main_branch',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'start_operation_hour',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'start_operation_hour',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'end_operation_hour',
                'description' => null,
                'mapping_type' => 'TIME',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'end_operation_hour',
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
