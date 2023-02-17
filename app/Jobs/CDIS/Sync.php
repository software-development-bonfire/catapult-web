<?php

namespace App\Jobs\CDIS;

use App\Enums\DeleteSyncedAction;
use App\Services\CDIS\SyncService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Sync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bids, $branchCode, $broadcast, $catapultActionType, $showProgress, $progressDivisor;
    /**
     * Create a new job instance.
     *
     * @param array $bids
     * @return void
     */
    public function __construct($bids, $branchCode, $broadcast, $catapultActionType, $progressDivisor, $showProgress = false)
    {
        $this->bids = $bids;
        $this->branchCode = $branchCode;
        $this->broadcast = $broadcast;
        $this->catapultActionType = $catapultActionType;
        $this->showProgress = $showProgress;
        $this->progressDivisor = $progressDivisor;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $syncService = app()->make(SyncService::class);

        $syncedDetails = $syncService->sync($this->bids, $this->branchCode, $this->broadcast, $this->catapultActionType, $this->progressDivisor, $this->showProgress);

        return $syncedDetails;
    }
	
	/**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // Send user notification of failure, etc...
    }
}
