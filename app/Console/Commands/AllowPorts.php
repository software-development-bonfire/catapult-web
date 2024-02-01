<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AllowPorts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'allow:port';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new firewall rule with port {port?}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $fileContent = file_get_contents(base_path('allow-ports.bat'));
        $fileContent = str_replace("6001", env('PUSHER_APP_PORT'), $fileContent);
        $fileContent = str_replace("80", env('CATAPULT_PORT'), $fileContent);
        $newFile = file_put_contents(base_path('allow-ports.bat'), $fileContent);

        if ($newFile) {
            exec(base_path('allow-ports.bat'), $output, $return);
            $this->info('Success!');
        } else {
            $this->info('Failed!');
        }
    }
}
