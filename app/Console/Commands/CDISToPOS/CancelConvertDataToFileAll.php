<?php

namespace App\Console\Commands\CDISToPOS;

use App\Enums\CatapultSyncStatus;
use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use App\Traits\JobCancellationTrait;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelConvertDataToFileAll extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:cancel-convert-all {--retry=5}{--broadcast=false}{--progress=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel conversion process for Generate CSV (All Data).';

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

        if ($broadcast) {
            $this->initializePusher();
        }

        // We need to check if conversion is already executed
        $isConverting = $this->isConverting();

        $attemptsCount = 0;
        $hasPendingJobs = true;
        do {
            $jobs = DB::table('jobs')->get();
            $failed_jobs = DB::table('failed_jobs')->get();

            DB::table('jobs')->delete();
            DB::table('failed_jobs')->delete();

            $hasPendingJobs = (count($jobs) > 0 || count($failed_jobs) > 0);

            if ($attemptsCount <= $retryCount) {
                $attemptsCount++;
            } else {
                break;
            }
        } while ($hasPendingJobs);

        $this->setCancelledConversion();
        $attempt = 0;
        while (! $this->hasBeenCancelledConversion()) {
            $this->setCancelledConversion();
            if ($attempt <= $retryCount) {
                $attempt++;
            } else {
                break;
            }
            $hasBeenCancelled = $this->hasBeenCancelledConversion();
            $this->createLog(__('info.cancelled_attempt', ['value' => $hasBeenCancelled, 'attempt' => $attempt]), 'info', true);
        }

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $this->createLog(__('info.generate_csv_all_data_cancelled').' @ ' . $this->secondsToHumanReadableTime($executionTime), 'info', true);

        // If conversion is not yet executed, then we must
        // send to CDIS that conversion been cancelled
        if ($broadcast && !$isConverting) {
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'ConversionDone', __('info.generate_csv_all_data_cancelled'), null);
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'catapult:status',  ['state' => CatapultSyncStatus::ConversionDone, 'code' => $branchCode], null);
            $this->clearCancelledConversion();
            $this->clearConverting();
        }
    }
}
