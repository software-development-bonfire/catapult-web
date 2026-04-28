<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\OutputInterface;

class ImportMappingSqlFiles extends Command
{
    protected $signature = 'pos:import-sql';
    protected $description = 'Import SQL files from mappings/branch directory in sequence with per-file validation';

    public function handle()
    {
        $path = base_path('mappings/EBC');
        //$database = env('DB_DATABASE');
        $database = config('database.connections.mysql.database'); 

        // Validate prerequisites
        if (!File::exists($path)) {
            $this->error("❌ Directory not found: {$path}");
            return 1;
        }

        if (!$database) {
            $this->error("❌ DB_DATABASE not configured in .env");
            return 1;
        }

        /**
         * Define execution order and sequence here
         * Each file will execute in the order specified below
         */
        $files = [
            0 => 'ebc_v1.sql',
            1 => 'ebc_field_mapping.20230217.144124.sql',
            2 => 'ebc_field_mapping.20230217.215730.sql',
            3 => 'ebc_field_mapping.20230525.103230.sql',
        ];

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        $this->line('');
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('SQL Import Sequence Started');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line("Database: <fg=cyan>{$database}</>");
        $this->line("Files to import: <fg=cyan>" . count($files) . "</>");
        $this->line('');

        DB::beginTransaction();

        try {
            // Set session variables for consistent behavior
            DB::statement("SET SESSION sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            DB::statement("SET SESSION foreign_key_checks = 0");

            // Execute USE statement dynamically from .env
            $createDatabaseStatement = "CREATE DATABASE IF NOT EXISTS `{$database}`";
            DB::unprepared($createDatabaseStatement);
            $useStatement = "USE `{$database}`";
            DB::unprepared($useStatement);
            $this->line("<fg=green>✓</> USE statement executed: <fg=cyan>{$database}</>");
            $this->line('');

            // Process each file in sequence
            foreach ($files as $sequence => $file) {
                $fileNumber = $sequence + 1;
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;

                // Validate file exists
                if (!File::exists($fullPath)) {
                    $errorMsg = "File not found: {$file}";
                    $this->error("[{$fileNumber}] ❌ {$errorMsg}");
                    $results[$file] = [
                        'status' => 'FAILED',
                        'message' => $errorMsg,
                        'sequence' => $fileNumber,
                    ];
                    $failureCount++;
                    continue;
                }

                try {
                    $this->line("[{$fileNumber}] Processing: <fg=yellow>{$file}</>");

                    // Get file size for logging
                    $fileSize = File::size($fullPath);
                    $this->line("    Size: <fg=cyan>" . $this->formatBytes($fileSize) . "</>");

                    // Read SQL content
                    $sql = File::get($fullPath);

                    // Parse and execute SQL statements individually
                    $executionStats = $this->executeSqlStatements($sql, $fileNumber);

                    if ($executionStats['failed'] > 0) {
                        throw new \Exception(
                            "Statements executed: {$executionStats['total']}, "
                            . "Successful: {$executionStats['successful']}, "
                            . "Failed: {$executionStats['failed']}"
                        );
                    }

                    // If we reach here, file executed successfully
                    $this->line("    Statements: <fg=cyan>{$executionStats['total']}</> | "
                        . "<fg=green>✓ {$executionStats['successful']}</> | "
                        . "<fg=red>✗ {$executionStats['failed']}</>");
                    $this->line("    <fg=green>✓ SUCCESS</> - File imported successfully");
                    $results[$file] = [
                        'status' => 'SUCCESS',
                        'message' => "All {$executionStats['total']} statements executed successfully",
                        'sequence' => $fileNumber,
                        'size' => $fileSize,
                        'statements' => $executionStats,
                    ];
                    $successCount++;
                    $this->line('');

                } catch (\Throwable $e) {
                    $errorMsg = $e->getMessage();
                    $this->error("    ❌ FAILED - {$errorMsg}");
                    $results[$file] = [
                        'status' => 'FAILED',
                        'message' => $errorMsg,
                        'sequence' => $fileNumber,
                    ];
                    $failureCount++;
                    $this->line('');

                    // Continue to next file or throw based on needs
                    // Uncomment below to stop on first failure
                    throw new \Exception("File import failed: {$file}");
                }
            }

            // Restore session variables
            DB::statement("SET SESSION foreign_key_checks = 1");

            // Commit transaction if all files succeeded
            if ($failureCount === 0) {
                DB::commit();
                $this->line('═══════════════════════════════════════════════════════');
                $this->info('✓ All files imported successfully - Transaction COMMITTED');
                $this->line('═══════════════════════════════════════════════════════');
            } else {
                DB::rollBack();
                $this->line('═══════════════════════════════════════════════════════');
                $this->error('❌ One or more files failed - Transaction ROLLED BACK');
                $this->line('═══════════════════════════════════════════════════════');
            }

            $this->line('');
            $this->printSummary($results, $successCount, $failureCount);

            return $failureCount > 0 ? 1 : 0;

        } catch (\Throwable $e) {
            DB::rollBack();
            DB::statement("SET SESSION foreign_key_checks = 1");

            $this->line('');
            $this->line('═══════════════════════════════════════════════════════');
            $this->error('❌ CRITICAL ERROR - Transaction ROLLED BACK');
            $this->line('═══════════════════════════════════════════════════════');
            $this->error("Error: " . $e->getMessage());
            $this->line('File: ' . $e->getFile());
            $this->line('Line: ' . $e->getLine());

            return 1;
        }
    }

    /**
     * Print execution summary
     *
     * @param array $results
     * @param int $successCount
     * @param int $failureCount
     * @return void
     */
    private function printSummary(array $results, int $successCount, int $failureCount): void
    {
        $this->info('IMPORT SUMMARY:');
        $this->line("  <fg=green>✓ Successful:</> <fg=cyan>{$successCount}</>");
        $this->line("  <fg=red>✗ Failed:</> <fg=cyan>{$failureCount}</>");
        $this->line("  Total: <fg=cyan>" . count($results) . "</>");

        if (!empty($results)) {
            $this->line('');
            $this->info('File Details:');
            foreach ($results as $file => $result) {
                $status = $result['status'] === 'SUCCESS' 
                    ? '<fg=green>✓ SUCCESS</>' 
                    : '<fg=red>✗ FAILED</>';
                $sequence = $result['sequence'];
                $this->line("  [{$sequence}] {$status} - {$file}");
                if ($result['status'] === 'FAILED') {
                    $this->line("      Error: {$result['message']}");
                }
            }
        }
        $this->line('');
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Parse SQL file and execute statements individually with validation
     *
     * @param string $sqlContent
     * @param int $fileNumber
     * @return array
     * @throws \Exception
     */
    private function executeSqlStatements(string $sqlContent, int $fileNumber): array
    {
        $total = 0;
        $successful = 0;
        $failed = 0;

        // Split SQL into individual statements
        $statements = $this->parseSqlStatements($sqlContent);

        if (empty($statements)) {
            return ['total' => 0, 'successful' => 0, 'failed' => 0];
        }

        foreach ($statements as $index => $statement) {
            $total++;
            $stmtNumber = $index + 1;

            try {
                // Execute statement
                DB::unprepared($statement);
                $successful++;

                $this->line("      [{$fileNumber}.{$stmtNumber}] <fg=green>✓</> Executed", OutputInterface::VERBOSITY_VERY_VERBOSE);

            } catch (\Throwable $e) {
                $failed++;
                $this->line("      [{$fileNumber}.{$stmtNumber}] <fg=red>✗</> Failed: " . substr($e->getMessage(), 0, 100), OutputInterface::VERBOSITY_DEBUG);
                
                // Re-throw to stop processing this file
                throw new \Exception("Statement {$stmtNumber} failed: " . $e->getMessage());
            }
        }

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * Parse SQL content into individual statements
     * Handles comments, removes MySQL control statements
     *
     * @param string $sql
     * @return array
     */
    private function parseSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';

        // Split by semicolon but keep track of string literals
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Skip SQL comments
            if (strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
                continue;
            }

            // Skip MySQL control comments (/*!... */)
            if (preg_match('/^\/\*![\d]+.*?\*\/;?$/', $line)) {
                continue;
            }

            // Skip generic MySQL comments
            if ($line === '/*!40000 ALTER TABLE' || 
                $line === '/*!40000 ALTER TABLE' ||
                preg_match('/^\/\*[\d]+.*/', $line)) {
                continue;
            }

            // Add line to current statement
            $current .= ' ' . $line;

            // Check if statement is complete (ends with semicolon)
            if (substr(trim($current), -1) === ';') {
                // Clean up the statement
                $cleanStmt = trim($current);
                if (!empty($cleanStmt) && $cleanStmt !== ';') {
                    $statements[] = $cleanStmt;
                }
                $current = '';
            }
        }

        // Add any remaining statement
        if (!empty(trim($current))) {
            $cleanStmt = trim($current);
            if (!empty($cleanStmt) && $cleanStmt !== ';') {
                $statements[] = $cleanStmt;
            }
        }

        return $statements;
    }
}