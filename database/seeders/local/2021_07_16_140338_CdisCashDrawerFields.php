<?php

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisCashDrawerFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $cashBreakdown = [
            'type' => '2',
            'api_version_name' => 'Cash Drawer default',
            'api_endpoint' => 'Cash Drawer',
            'status' => 1,
            'created_by' => 1,
        ];

        $field_map = FieldMapping::create($cashBreakdown);

        $field_mapping_details = [
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'cash_drawer_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 1,
                'column_name' => 'bid',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'branch_code',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'branch_code',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'terminal_number',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'terminal_number',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'cashier_id',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'cashier_id',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'cashier_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'cashier_name',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'amount',
                'description' => null,
                'mapping_type' => 'DECIMAL',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'amount',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'date',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'approver_id',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'approver',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'approver_name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'approver_name',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'approved_date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'approved_date',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'type',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'type',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'remarks',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'DR',
                'default_value' => 0,
                'column_name' => 'remarks',
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
