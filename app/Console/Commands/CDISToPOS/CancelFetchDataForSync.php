<?php

namespace App\Console\Commands\CDISToPOS;

use App\Services\CDIS\SyncService;
use App\Traits\GenericHelper;
use App\Traits\PusherTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CancelFetchDataForSync extends Command
{
    use GenericHelper, PusherTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdis:cancel-sync {--retry=5}{--broadcast=false}{--progress=false}';

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
		$branchCode = config('configuration.branch_code');

        $retryCount = $this->option('retry');
        $retryCount = filter_var($retryCount, FILTER_VALIDATE_INT) ? (int) $retryCount: 0;

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

        $this->createLog(
            'Canceling...',
            'info',
            true
        );

        $timeStart = microtime(true);

		if ($broadcast) {
			$this->initializePusher();
		}

        $attemptsCount = 0;
        $hasPendingJobs = true;
        do {
            $jobs           = DB::table('jobs')->get();
            $failed_jobs    = DB::table('failed_jobs')->get();

			DB::table('jobs')->delete();
			DB::table('failed_jobs')->truncate();

            Artisan::queue('queue:flush');
            Artisan::queue('queue:restart');
            
            $hasPendingJobs = (count($jobs) > 0 || count($failed_jobs) > 0);

            $this->createLog('Canceling...', 'info', true, ['Jobs: '.count($jobs), 'Failed Jobs: '.count($failed_jobs)]);

            if ($attemptsCount <= $retryCount) {
                $attemptsCount++;
            } else {
                break;
            }
        }
        while ($hasPendingJobs);
        
        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);

        $this->createLog('Sync Cancelled! @ '.$this->secondsToHumanReadableTime($executionTime), 'info', true);

        if ($broadcast) {
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'SyncDone', 'Sync Cancelled! @ '. $this->secondsToHumanReadableTime($executionTime), null);
        }
    }
}
