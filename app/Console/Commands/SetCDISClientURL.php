<?php

namespace App\Console\Commands;

use App\Traits\ApiSetupTrait;
use App\Traits\CatapultConfiguration;
use Illuminate\Console\Command;

class SetCDISClientURL extends Command
{
    use CatapultConfiguration;
    use ApiSetupTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:client_url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the CDIS Client URL';

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
        // Check if api setup already configured
        // If not, most probably seeder is not yet ran
        if ($this->hasApiSetup()) {
            $cdisURL = $this->ask('Enter Client CDIS URL');
            if (! isset($cdisURL)) {
                // If no entered client CDIS URL
                // then we use url from .ENV file
                $cdisURL = config('app.cdis_url');
            }

            $this->updateApiDomain($cdisURL);
        } else {
            $this->error(__('info.you_run_seeder_first'));
        }
    }
}
