<?php

namespace App\Console\Commands\CDISToPOS;

use App\Enums\CatapultSyncStatus;
use App\Traits\GenericHelper;
use App\Traits\JobCancellationTrait;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelFetchDataForSync extends Command
{
    use GenericHelper, PusherTrait, JobCancellationTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:cancel-sync {--retry=5}{--broadcast=true}{--progress=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel Fetching, listening, get and validating "for sync" row data in CDIS sync table';

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

        $retryCount = $this->option('retry');
        $retryCount = filter_var($retryCount, FILTER_VALIDATE_INT) ? (int) $retryCount : 0;

        $broadcast = $this->option('broadcast');
        $broadcast = filter_var($broadcast, FILTER_VALIDATE_BOOLEAN);

        $showProgress = $this->option('progress');
        $showProgress = filter_var($showProgress, FILTER_VALIDATE_BOOLEAN);

        $this->createLog(
            __('info.cancelling'),
            'info',
            true
        );

        if ($broadcast) {
            $this->initializePusher();
        }
        // We need to check if syncing is already executed
        $isSyncing = $this->isSyncing();

        $attemptsCount = 0;
        $hasPendingJobs = true;
        do {
            $jobs = DB::table('jobs')->get();
            $failed_jobs = DB::table('failed_jobs')->get();

            DB::table('jobs')->truncate();
            DB::table('failed_jobs')->truncate();

            foreach ($jobs as $job) {
                DB::table('jobs')->delete($job->id);
            }
            $hasPendingJobs = (count($jobs) > 0 || count($failed_jobs) > 0);

            if ($attemptsCount <= $retryCount) {
                $attemptsCount++;
            } else {
                break;
            }
        } while ($hasPendingJobs);

        $hasBeenCancelled = false;
        $attemptsCount = 0;
        do {
            $this->setCancelledSyncing();
            $hasBeenCancelled = $this->hasBeenCancelledSyncing();
            $this->createLog(__('info.cancelled_attempt', ['value' => $hasBeenCancelled, 'attempt' => $attemptsCount]), 'info', true);

            if ($attemptsCount <= $retryCount) {
                $attemptsCount++;
            } else {
                break;
            }
        } while (! $hasBeenCancelled);

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $this->createLog(__('info.syncing_cancelled').' @ '.$this->secondsToHumanReadableTime($executionTime), 'info', true);

        // If syncing is not yet executed, then we must
        // send to CDIS that syncing been cancelled
        if ($broadcast && !$isSyncing) {
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'SyncDone', __('info.syncing_cancelled'), null);
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'catapult:status',  ['state' => CatapultSyncStatus::SyncDone, 'code' => $branchCode], null);
            $this->clearCancelledSyncing();
            $this->clearSyncing();
        }
    }
}
