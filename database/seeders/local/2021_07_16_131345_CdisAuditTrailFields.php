<?php

use App\Entities\FieldMapping;
use App\Entities\FieldMappingDetail;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisAuditTrailFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $auditTrail = [
            'type' => '2',
            'api_version_name' => 'Audit Trail default',
            'api_endpoint' => 'Audit Trail',
            'status' => 1,
            'created_by' => 1,
        ];

        $field_map = FieldMapping::create($auditTrail);

        $field_mapping_details = [
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'branch_code',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'AT',
                'default_value' => 0,
                'column_name' => 'branch_code',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'terminal_no',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'terminal_no',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'date',
                'description' => null,
                'mapping_type' => 'DATETIME',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'date',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'application',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'application',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'cashier',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'cashier',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'supervisor',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'supervisor',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'job',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
                'column_name' => 'job',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'transaction_no',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'AT',
                'default_value' => 1,
                'column_name' => 'transaction_no',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'receipt_no',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => 'AT',
                'default_value' => 1,
                'column_name' => 'receipt_no',
            ],
            [
                'field_mapping_bid' => $field_map->bid,
                'required' => 0,
                'field' => 'remarks',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => 'AT',
                'default_value' => '""',
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
