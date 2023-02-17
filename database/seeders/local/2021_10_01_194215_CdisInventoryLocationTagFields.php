<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisInventoryLocationTagFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $inventoryLocation = [
            'type' => '1',
            'preset_name' => 'Inventory Location Tag (Default)',
            'data_entry' => 'inventory_location_tag',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($inventoryLocation);

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
                'default_value' => '""',
                'column_name' => 'head_bid',
            ],
            [
                'required' => 1,
                'field' => 'type',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'type',
            ],
            [
                'required' => 1,
                'field' => 'inventory_location_bid',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'inventory_location_bid',
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
            'preset_name' => 'Inventory Location Tag (Default)',
            'data_entry' => 'inventory_location_tag',
        ])->forceDelete();
    }
}
