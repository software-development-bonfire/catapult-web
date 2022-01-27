<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisUnitOfMeasurement extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $unitOfMeasurement = [
            'type' => '1',
            'preset_name' => 'Unit of Measurement (Default)',
            'data_entry' => 'unit_of_measurement',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($unitOfMeasurement);

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
                'field' => 'name',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'name',
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
            'preset_name' => 'Unit of Measurement (Default)',
            'data_entry' => 'unit_of_measurement',
        ])->forceDelete();
    }
}
