<?php

namespace App\Console\Commands\Tools;

use App\Entities\ApiSetup;
use App\Entities\FileStorageSetup;
use App\Entities\TerminalFileSetup;
use App\Helpers\WindowsBaseDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ResolvePath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resolve:path';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve and update CDIS_PATH in file storage and terminal configurations from .env configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cdisPath = config('app.cdis_path');
        $hardcodedPath = 'C:\\Users\\POS\\Documents\\CDIS';


        if (!$cdisPath) {
            $this->error('❌ CDIS_PATH is not set in .env file.');
            return 1;
        }

        $this->line('');
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('Path Resolution Command Started');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line("CDIS_PATH from .ENV: <fg=cyan>{$cdisPath}</>");
        $this->line("Hardcoded Path to replace: <fg=yellow>{$hardcodedPath}</>");
        $this->line('');

        try {
            // Update File Storage Setups
            $this->info('Processing File Storage Setups...');
            $storageUpdatedCount = $this->updateFileStorageSetups($hardcodedPath, $cdisPath);
            $this->line('');

            // Update Terminal File Setups
            $this->info('Processing Terminal File Setups...');
            $terminalUpdatedCount = $this->updateTerminalFileSetups($hardcodedPath, $cdisPath);
            $this->line('');

            // Print summary
            $this->printSummary($storageUpdatedCount, $terminalUpdatedCount);

            return 0;

        } catch (\Throwable $e) {
            $this->line('');
            $this->error('❌ Error occurred: ' . $e->getMessage());
            return 1;
        }
    }


    /**
     * Update File Storage Setups with new CDIS_PATH
     *
     * @param string $hardcodedPath
     * @param string $newPath
     * @return int Number of records updated
     */
    private function updateFileStorageSetups(string $hardcodedPath, string $newPath): int
    {
        $storageSetups = FileStorageSetup::all();
        $updatedCount = 0;

        foreach ($storageSetups as $setup) {
            $updated = false;
            $oldLocalPath = $setup->local_path;
            $oldRemotePath = $setup->remote_path;

            // Update local_path if it contains hardcoded path
            if (stripos($setup->local_path, $hardcodedPath) !== false) {
                $setup->local_path = str_ireplace($hardcodedPath, $newPath, $setup->local_path);
                $updated = true;
            }

            // Update remote_path if it contains hardcoded path
            if (stripos($setup->remote_path, $hardcodedPath) !== false) {
                $setup->remote_path = str_ireplace($hardcodedPath, $newPath, $setup->remote_path);
                $updated = true;
            }

            if ($updated) {
                $setup->save();
                $updatedCount++;

                $this->line("  <fg=green>✓</> Updated: <fg=yellow>{$setup->name}</>");
                
                if ($oldLocalPath !== $setup->local_path) {
                    $this->line("      Local Path From: <fg=cyan>{$oldLocalPath}</>");
                    $this->line("      Local Path To:   <fg=green>{$setup->local_path}</>");
                }

                if ($oldRemotePath !== $setup->remote_path) {
                    $this->line("      Remote Path From: <fg=cyan>{$oldRemotePath}</>");
                    $this->line("      Remote Path To:   <fg=green>{$setup->remote_path}</>");
                }
            }
        }

        if ($updatedCount === 0) {
            $this->line("  <fg=yellow>No file storage setups needed updating.</>");
        } else {
            $this->line("  <fg=green>✓ {$updatedCount}</> file storage setup(s) updated.");
        }

        return $updatedCount;
    }

    /**
     * Update Terminal File Setups with new CDIS_PATH
     *
     * @param string $hardcodedPath
     * @param string $newPath
     * @return int Number of records updated
     */
    private function updateTerminalFileSetups(string $hardcodedPath, string $newPath): int
    {
        $terminalSetups = TerminalFileSetup::all();
        $updatedCount = 0;

        foreach ($terminalSetups as $setup) {
            $updated = false;
            $oldTerminalPath = $setup->terminal_path;
            $oldSubDirectories = $setup->sub_directories;

            // Update terminal_path if it contains hardcoded path
            if (stripos($setup->terminal_path, $hardcodedPath) !== false) {
                $setup->terminal_path = str_ireplace($hardcodedPath, $newPath, $setup->terminal_path);
                $updated = true;
            }

            // Update sub_directories if it contains hardcoded path
            if (!empty($setup->sub_directories) && stripos($setup->sub_directories, $hardcodedPath) !== false) {
                $setup->sub_directories = str_ireplace($hardcodedPath, $newPath, $setup->sub_directories);
                $updated = true;
            }

            if ($updated) {
                $setup->save();
                $updatedCount++;

                $this->line("  <fg=green>✓</> Updated: <fg=yellow>{$setup->name}</> (Code: {$setup->terminal_code})");
                
                if ($oldTerminalPath !== $setup->terminal_path) {
                    $this->line("      Terminal Path From: <fg=cyan>{$oldTerminalPath}</>");
                    $this->line("      Terminal Path To:   <fg=green>{$setup->terminal_path}</>");
                }

                if ($oldSubDirectories !== $setup->sub_directories) {
                    $this->line("      Sub Directories From: <fg=cyan>{$oldSubDirectories}</>");
                    $this->line("      Sub Directories To:   <fg=green>{$setup->sub_directories}</>");
                }
            }
        }

        if ($updatedCount === 0) {
            $this->line("  <fg=yellow>No terminal file setups needed updating.</>");
        } else {
            $this->line("  <fg=green>✓ {$updatedCount}</> terminal file setup(s) updated.");
        }

        return $updatedCount;
    }

    /**
     * Print summary of changes
     *
     * @param int $storageCount
     * @param int $terminalCount
     * @return void
     */
    private function printSummary(int $storageCount, int $terminalCount): void
    {
        $this->line('═══════════════════════════════════════════════════════');
        $this->info('Path Resolution Complete');
        $this->line('═══════════════════════════════════════════════════════');
        $this->line("File Storage Setups Updated:   <fg=cyan>{$storageCount}</>");
        $this->line("Terminal File Setups Updated:  <fg=cyan>{$terminalCount}</>");
        $this->line("Total Updates:                 <fg=green>" . ($storageCount + $terminalCount) . "</>");
        $this->line('');
    }
}
