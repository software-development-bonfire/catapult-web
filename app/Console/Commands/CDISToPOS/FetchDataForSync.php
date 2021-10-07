<?php

namespace App\Console\Commands\CDISToPOS;

use App\Services\CDIS\SyncService;
use App\Traits\GenericHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FetchDataForSync extends Command
{
    use GenericHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:fetch-data-for-sync';

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

        $this->createLog(
            __('info.syncing_started'),
            'info',
            true
        );

        do {
            $forSync = $syncService->forSync();

            if (! isset($forSync->bidsChunks)) {
                $this->createLog(__('message.no_data_to_sync'), 'info', true);
            } else if (isset($forSync->bidsChunks) && $forSync->bidsChunks > 0) {
                $this->createLog('---------------------------------------------------------', 'info', false);
                $this->createLog('Action count: '.$forSync->action_count.' | Entry count: '.$forSync->entry_count, 'info', true);
                foreach ($forSync->bidsChunks as $bidsChunk) {
                    foreach ($bidsChunk as $bid) {
                        $this->createLog(__('success.queued_to_sync'), 'info', true, [$bid]);
                    }

                }
            }

            sleep(5);
        }
        while (true);
    }
}
