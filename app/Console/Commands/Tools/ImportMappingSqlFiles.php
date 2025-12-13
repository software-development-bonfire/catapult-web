<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportMappingSqlFiles extends Command
{
    protected $signature = 'pos:import-sql';
    protected $description = 'Import SQL files from mappings/branch directory in sequence';

    public function handle()
    {
        $path = base_path('mappings/EBC');

        if (!File::exists($path)) {
            $this->error("Directory not found: {$path}");
            return;
        }

        /**
         * Define execution order here
         */
        $files = [
            'ebc_v1.sql',
            'ebc_field_mapping.20230217.144124.sql',
            'ebc_field_mapping.20230217.215730.sql',
            'ebc_field_mapping.20230525.103230.sql',
        ];

        DB::beginTransaction();

        try {
            foreach ($files as $file) {
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;

                if (!File::exists($fullPath)) {
                    throw new \Exception("Missing SQL file: {$file}");
                }

                $this->info("Importing: {$file}");

                $sql = File::get($fullPath);

                // Important: split by delimiter if needed
                DB::unprepared($sql);
            }

            DB::commit();
            $this->info('✅ All SQL files imported successfully.');

            return;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("❌ Import failed: " . $e->getMessage());

            return;
        }
    }
}
