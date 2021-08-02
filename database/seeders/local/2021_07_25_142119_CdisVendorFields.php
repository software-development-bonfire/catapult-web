<?php

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisVendorFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $vendor = [
            'type' => '1',
            'api_version_name' => 'Vendor default',
            'api_endpoint' => 'Vendor',
            'status' => 1,
            'created_by' => 1,
        ];

        $field_map = FieldMapping::create($vendor);

        $field_mapping_details = [
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'bid',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'code',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'code',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'description',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'description',
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
                'field' => 'payment_term_days',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'payment_term_days',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'tin_no',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'tin_no',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'email',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'email',
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
                'field' => 'status',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 1,
                'column_name' => 'status',
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
