<?php

namespace App\Console\Commands\Tools;

use App\Traits\JobCancellationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DynamicCommands extends Command
{
    use JobCancellationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:cmd {command?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute artisan commands.';

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
        $this->info('Executing commands from CDIS...');

        $command = $this->argument('command');

        if (isset($command)) {
            $this->info($command);
            Artisan::callSilent($command);
        }
    }
}
