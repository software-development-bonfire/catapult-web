<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all log files in storage/logs except .gitignore';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $logPath = storage_path('logs'); // Get the full path to the logs directory

        // Check if the logs directory exists
        if (!is_dir($logPath)) {
            $this->error('The logs directory does not exist.');
            return;
        }

        // Get all files in the logs directory
        $files = File::files($logPath);

        foreach ($files as $file) {
            // Skip the .gitignore file
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname()); // Delete the file
            }
        }

        $this->info('Log files cleared successfully, except .gitignore.');
    }
}
