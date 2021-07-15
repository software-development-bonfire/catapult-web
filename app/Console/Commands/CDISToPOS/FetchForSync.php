<?php

namespace App\Console\Commands\CDISToPOS;

use App\Services\CDIS\SyncService;
use Illuminate\Console\Command;

class FetchForSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch-for-sync:cdis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch, listen, get and validate "for sync" row data in CDIS sync table';

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
        $syncService = app()->make(SyncService::class);

        $this->line('Listening for scheduled tasks..');

        while (true) {
            if (now()->startOfMinute()->is(now())) {
                $forSync = $syncService->forSync();

                if (! isset($forSync->bids)) {
                    $this->info('Count: '.$forSync->count);
                    $this->info('Total: '.$forSync->total);
                    $this->info('');
                    $this->info('Note: '. __('message.no_data_to_sync'));
                } else if (isset($forSync->bids) && $forSync->bids > 0) {
                    $this->info('Count: '.$forSync->count);
                    $this->info('Total: '.$forSync->total);
                    $this->info('');

                    foreach ($forSync->bids as $bid) {
                        $this->info(__('success.value_successfully_synced', ['value' => "[".$bid."]"]));
                    }
                }
            }

            sleep(1);
        }
    }
}
