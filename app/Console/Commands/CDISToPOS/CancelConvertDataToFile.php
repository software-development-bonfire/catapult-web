<?php

namespace App\Console\Commands\CDISToPOS;

use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use App\Traits\JobCancellationTrait;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelConvertDataToFile extends Command
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:cancel-convert {--retry=5}{--broadcast=false}{--progress=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel conversion process.';

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
        $attemptsCount = 0;
        while (! $this->hasBeenCancelledConversion()) {
            $this->setCancelledConversion();
            if ($attemptsCount <= $retryCount) {
                $attemptsCount++;
            } else {
                break;
            }
            $hasBeenCancelled = $this->hasBeenCancelledConversion();
            $this->createLog('Has been cancelled? '.$hasBeenCancelled, 'info', true);
            $this->createLog('Cancelling attempt @ '.$attemptsCount, 'info', true);
        }

        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $this->createLog('Cancelling takes @ '.$this->secondsToHumanReadableTime($executionTime), 'info', true);

        // If conversion is not yet executed, then we must
        // send to CDIS that conversion been cancelled
        if ($broadcast && !$isConverting) {
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'ConversionDone', __('info.create_csv_for_new_branch_cancelled'), null);
            $this->clearCancelledConversion();
            $this->clearConverting();
        }
    }
}
