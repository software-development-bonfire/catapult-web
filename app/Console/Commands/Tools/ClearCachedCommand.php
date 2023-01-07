<?php

namespace App\Console\Commands\Tools;

use App\Traits\JobCancellationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearCachedCommand extends Command
{
    use JobCancellationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:cache {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all pre-defined caches.';

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
        $this->info('Clearing defined cache...');

        $cacheName = $this->argument('name');

        if (isset($cacheName)) {
            if ($cacheName === 'sync') {
                $this->clearSyncing();
                $this->clearCancelledSyncing();
            } else {
                $this->clearConverting();
                $this->clearCancelledConversion();
            }
        } else {
            $this->clearSyncing();
            $this->clearCancelledSyncing();
            $this->clearConverting();
            $this->clearCancelledConversion();
        }

        $this->info('Cleared success!');
    }
}
