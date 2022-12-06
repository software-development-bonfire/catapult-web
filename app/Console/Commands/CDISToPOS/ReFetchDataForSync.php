<?php

namespace App\Console\Commands\CDISToPOS;

use App\Enums\CatapultActionType;
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
    protected $signature = 'cdis:fetch-data-for-sync-again {--interval=true}{--limit=true}{--table=all}{--broadcast=false}{--type=EVENT}{--progress=false}{--progress_divisor=100}';

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
        $interval = toBooleanOrInt($interval, config('sync.cdis.to_catapult.interval'));

        $limit = $this->option('limit');
        $limit = toBooleanOrInt($limit, config('sync.cdis.to_catapult.limit'));

        $broadcast = $this->option('broadcast');
        $broadcast = toBooleanOrInt($broadcast, config('sync.cdis.to_catapult.broadcast'));

        $showProgress = $this->option('progress');
        $showProgress = filter_var($showProgress, FILTER_VALIDATE_BOOLEAN);

        $table = $this->option('table');
        $catapultActionType = $this->option('type');

        $progressDivisor = $this->option('progress_divisor');
        $progressDivisor = toBooleanOrInt($progressDivisor, config('sync.cdis.to_catapult.progress_divisor'));

        $syncService = app()->make(SyncService::class);

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

            $forSync = $syncService->forSync($limit, $table, $broadcast, $catapultActionType, $progressDivisor, $showProgress);

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

            $convertCommand = null;
            if ($catapultActionType === CatapultActionType::NEW_BRANCH) {
                $convertCommand = 'cdis:convert-data-to-file';
            } else if ($catapultActionType === CatapultActionType::ALL) {
                $convertCommand = 'cdis:convert-data-to-file-all';
            } else if ($catapultActionType === CatapultActionType::CHANGES) {
                $convertCommand = 'cdis:convert-data-to-file-changes';
            } else {
                $convertCommand = null;
            }

            if (! empty($convertCommand)) {
                $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'catapult:status',  [
                    'state' => CatapultSyncStatus::Converting, 
                    'code' => $branchCode, 
                    'description' => $catapultActionType
                ], null);

                $this->setSyncStatus(CatapultSyncStatus::Converting);
                Artisan::queue($convertCommand, [
                    '--interval' => $interval,
                    '--limit' => $limit,
                    '--broadcast' =>  $broadcast,
                    '--progress' => $showProgress,
                    '--progress_divisor' => $progressDivisor,
                    '--type' => $catapultActionType
                ]);
            }
        }
    }
}
