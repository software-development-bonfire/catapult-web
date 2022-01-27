<?php

use App\Entities\FieldMappingPreset;
use Eighty8\LaravelSeeder\Migration\MigratableSeeder;
use Eighty8\LaravelSeeder\Repository\DisableForeignKeysTrait;

class CdisKitchenUserFields extends MigratableSeeder
{
    use DisableForeignKeysTrait;

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $kitchenUser = [
            'type' => '1',
            'preset_name' => 'Kitchen User (Default)',
            'data_entry' => 'kitchen_user',
            'status' => 1,
            'created_by' => 1,
        ];

        $fieldMappingPreset = FieldMappingPreset::create($kitchenUser);

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
                'field' => 'username',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'username',
            ],
            [
                'required' => 1,
                'field' => 'password',
                'description' => null,
                'mapping_type' => 'VARCHAR',
                'file_name' => '""',
                'default_value' => '""',
                'column_name' => 'password',
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
            'preset_name' => 'Kitchen User (Default)',
            'data_entry' => 'kitchen_user',
        ])->forceDelete();
    }
}
