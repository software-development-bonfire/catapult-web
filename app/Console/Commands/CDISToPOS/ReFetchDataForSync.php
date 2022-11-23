<?php

namespace App\Console\Commands\CDISToPOS;

use App\Enums\CatapultSyncStatus;
use App\Enums\DeleteSyncedAction;
use App\Services\CDIS\SyncService;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use App\Traits\JobCancellationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReFetchDataForSync extends Command
{
    use GenericHelper, PusherTrait, JobCancellationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:fetch-data-for-sync-again {--interval=true}{--limit=true}{--table=all}{--broadcast=false}{--progress=false}{--type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch, listen, get and validate "for sync" row data in CDIS sync table';


    /**
     * Set to true if you want to show syncing progress to CDIS activity
     */
    private $showSyncStatus = false;
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
        $timeStart = microtime(true);

        $branchCode = config('configuration.branch_code');

        $interval = $this->option('interval');
        $interval =
            filter_var($interval, FILTER_VALIDATE_BOOLEAN)
            ? config('sync.cdis.to_catapult.interval')
            : ((int) $interval
                ? filter_var($interval, FILTER_VALIDATE_INT)
                : false
            );

        $limit = $this->option('limit');
        $limit =
            filter_var($limit, FILTER_VALIDATE_BOOLEAN)
            ? config('sync.cdis.to_catapult.limit')
            : ((int) $limit
                ? filter_var($limit, FILTER_VALIDATE_INT)
                : false
            );

        $broadcast = $this->option('broadcast');
        $broadcast = filter_var($broadcast, FILTER_VALIDATE_BOOLEAN);

        $showProgress = $this->option('progress');
        $showProgress = filter_var($showProgress, FILTER_VALIDATE_BOOLEAN);

        $table = $this->option('table');

        $generateType = $this->option('type');

        $deleteSyncedAction = ($generateType === 'changes') ? DeleteSyncedAction::CONVERT_EVENT : DeleteSyncedAction::CONVERT_ALL;

        $syncService = app()->make(SyncService::class);

        $this->setSyncing();
        $this->clearCancelledConversion();
        $this->clearConverting();

        if ($broadcast) {
            $this->initializePusher();
        }

        Cache::forget('cdis_fetching_data_for_sync');

        $hasBeenCancelled = $this->hasBeenCancelledSyncing();

        $noDataToSync = false;

        do {
            if ($hasBeenCancelled) {
                break;
            }

            $forSync = $syncService->forSync($limit, $table, $broadcast, $deleteSyncedAction, $showProgress, true);

            if (! isset($forSync->bidsChunks)) {
                $noDataToSync = true;
            } else if (isset($forSync->bidsChunks) && $forSync->bidsChunks > 0) {
                Cache::forever('cdis_fetching_data_for_sync', true);
                $progress = 0;
                foreach ($forSync->bidsChunks as $bidsChunk) {
                    $progress++;
                    foreach ($bidsChunk as $bid) {
                        $this->createLog(__('success.queued_to_sync'), 'info', true, [$bid]);
                        $hasBeenCancelled = $this->hasBeenCancelledSyncing();
                        if ($hasBeenCancelled) {
                            break;
                        }
                    }

                    if ($hasBeenCancelled) {
                        break;
                    }
                }
            }

            if (is_int($interval)) {
                sleep($interval);
            } else {
                break;
            }
        } while (true);

        if ($noDataToSync) { 
            $this->clearCancelledSyncing();
            $this->clearSyncing();

            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'catapult:status',  ['state' => CatapultSyncStatus::Converting, 'code' => $branchCode], null);
            if ($generateType === 'changes') {
                Artisan::queue('cdis:convert-data-to-file-event', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
            } else {
                Artisan::queue('cdis:convert-data-to-file-all', ['--interval' => 'false', '--limit' => '9999999', '--broadcast' => 'true', '--progress' => 'false']);
            }
            
        }
    }
}
