<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CatapultAllowPort extends Command
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
    protected $description = 'Create new firewall rule with port 6001.';

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
        $runAsAdmin = exec(base_path('allow-port-6001.bat'), $output, $return);
        $this->info('Success!');
    }
}
