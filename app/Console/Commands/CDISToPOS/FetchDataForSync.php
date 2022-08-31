<?php

namespace App\Console\Commands\CDISToPOS;

use App\Services\CDIS\SyncService;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use App\Traits\JobCancellationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchDataForSync extends Command
{
    use GenericHelper, PusherTrait, JobCancellationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:fetch-data-for-sync {--interval=true}{--limit=true}{--table=all}{--broadcast=false}{--progress=false}';

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

        $syncService = app()->make(SyncService::class);

        $this->createLog(
            __('info.syncing_started'),
            'info',
            true
        );

        $this->setSyncing();
        $this->clearCancelledConversion();
        $this->clearConverting();

        if ($broadcast) {
            $this->initializePusher();
        }

        Cache::forget('cdis_fetching_data_for_sync');

        $hasBeenCancelled = $this->hasBeenCancelledSyncing();

        do {
            if ($hasBeenCancelled) {
                break;
            }

            $forSync = $syncService->forSync($limit, $table, $broadcast, false, $showProgress);

            if (!isset($forSync->bidsChunks)) {
                Cache::forget('cdis_fetching_data_for_sync');
                $this->createLog(__('message.no_data_to_sync'), 'info', true);
                if ($broadcast) {
                    Log::alert(__('message.no_data_to_sync'));
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'SyncDone', __('message.no_data_to_sync'), null);
                }
            } else if (isset($forSync->bidsChunks) && $forSync->bidsChunks > 0) {
                Cache::forever('cdis_fetching_data_for_sync', true);
                $this->createLog('---------------------------------------------------------', 'info', false);
                $this->createLog('Action count: '.$forSync->action_count.' | Entry count: '.$forSync->entry_count, 'info', true);
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

                    if ($showProgress && $broadcast) {
                        $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Syncing', __('info.fetching').$progress.' of '.count($forSync->bidsChunks), null);
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

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        Log::alert(__('info.fetch_success').' @ '.$this->secondsToHumanReadableTime($executionTime));
        $this->createLog(__('info.fetch_success').' @ '.$this->secondsToHumanReadableTime($executionTime), 'info', true);

        // If syncing is not yet executed, then we must
        // send to CDIS that syncing been cancelled
        if ($broadcast && $hasBeenCancelled) {
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'SyncDone', __('info.syncing_cancelled'), null);
            $this->clearCancelledSyncing();
            $this->clearSyncing();
        }
    }
}
