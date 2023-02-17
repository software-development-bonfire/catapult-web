<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisKitchenStationFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $kitchenStation = [
            'type' => '1',
            'preset_name' => 'Kitchen Station (Default)',
            'data_entry' => 'kitchen_station',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($kitchenStation);

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
                'field' => 'code',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'code',
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
            [
                'required' => 1,
                'field' => 'queueing_group_type',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'queueing_group_type',
            ],
            [
                'required' => 1,
                'field' => 'screen_prioritization',
                'description' => null,
                'mapping_type' => 'INT',
                'file_name' => '""',
                'default_value' => 0,
                'column_name' => 'screen_prioritization',
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
            'preset_name' => 'Kitchen Station (Default)',
            'data_entry' => 'kitchen_station',
        ])->forceDelete();
    }
}
