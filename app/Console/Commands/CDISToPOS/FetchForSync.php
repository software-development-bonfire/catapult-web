<?php

namespace App\Console\Commands\CDISToPOS;

use App\Services\CDIS\SyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

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
        $interval = config('sync.cdis.to_catapult.interval');
        $syncService = app()->make(SyncService::class);

        $this->line('Syncing started..');
        $this->line('');

        do {
            $this->line('Checking for sync...');
            $forSync = $syncService->forSync();

            if (! isset($forSync->bidsChunks)) {
                $this->info('Count: '.$forSync->count);
                $this->info('Total: '.$forSync->total);
                $this->info('Note: '. __('message.no_data_to_sync'));
            } else if (isset($forSync->bidsChunks) && $forSync->bidsChunks > 0) {
                $this->info('Count: '.$forSync->count);
                $this->info('Total: '.$forSync->total);

                $this->info('---------------------------------------');
                foreach ($forSync->bidsChunks as $bidsChunk) {
                    foreach ($bidsChunk as $bid) {
                        $this->info(__('success.value_queued_to_sync', ['value' => "[".$bid."]"]). ' |');
                    }

                }
                $this->info('---------------------------------------');
            }

            $this->info('');

            sleep($interval);
        }
        while (true);
    }
}
