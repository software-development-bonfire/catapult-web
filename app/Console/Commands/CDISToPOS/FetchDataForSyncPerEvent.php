<?php

namespace App\Console\Commands\CDISToPOS;

use App\Services\CDIS\SyncService;
use App\Traits\GenericHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchDataForSyncPerEvent extends Command
{
    use GenericHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:fetch-data-for-sync-event {--interval=true}{--limit=true}{--table=all}{--broadcast=false}{--progress=false}';

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
        $interval = $this->option('interval');
        $interval =
            filter_var($interval, FILTER_VALIDATE_BOOLEAN)
                ? config('sync.cdis.to_catapult.interval')
                : (
                    (int) $interval
                        ? filter_var($interval, FILTER_VALIDATE_INT)
                        : false
                    );

        $limit = $this->option('limit');
        $limit =
            filter_var($limit, FILTER_VALIDATE_BOOLEAN)
                ? config('sync.cdis.to_catapult.limit')
                : (
            (int) $limit
                ? filter_var($limit, FILTER_VALIDATE_INT)
                : false
            );

        $broadcast = $this->option('broadcast');
        $broadcast =
            filter_var($broadcast, FILTER_VALIDATE_BOOLEAN)
                ? config('sync.cdis.to_catapult.broadcast')
                : (
            (int) $broadcast
                ? filter_var($broadcast, FILTER_VALIDATE_INT)
                : false
            );
		
		$showProgress = $this->option('progress');
        $showProgress = filter_var($showProgress, FILTER_VALIDATE_BOOLEAN);
     
        $table = $this->option('table');

        $syncService = app()->make(SyncService::class);

        $this->createLog(
            __('info.syncing_started'),
            'info',
            true
        );

        Cache::forget('cdis_fetching_data_for_sync');

       
        do {
            $forSync = $syncService->forSync($limit, $table, $broadcast, true);

            if (! isset($forSync->bidsChunks)) {
                Cache::forget('cdis_fetching_data_for_sync');
                $this->createLog(__('message.no_data_to_sync'), 'info', true);
            } else if (isset($forSync->bidsChunks) && $forSync->bidsChunks > 0) {
                Cache::forever('cdis_fetching_data_for_sync', true);
                $this->createLog('---------------------------------------------------------', 'info', false);
                $this->createLog('Action count: '.$forSync->action_count.' | Entry count: '.$forSync->entry_count, 'info', true);
                $progress = 1;
                foreach ($forSync->bidsChunks as $bidsChunk) {
                    foreach ($bidsChunk as $bid) {
                        $this->createLog(__('success.queued_to_sync'). $progress.' of '.$forSync->action_count, 'info', true, [$bid]);
                        $progress++;
                    }
                }
            }

            if (is_int($interval)) {
                sleep($interval);
            } else {
                break;
            }
        }
        while (true);
    }
}
