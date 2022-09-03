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

    public $bids, $branchCode, $broadcast, $deleteSyncedAction, $showProgress;
    /**
     * Create a new job instance.
     *
     * @param array $bids
     * @return void
     */
    public function __construct($bids, $branchCode, $broadcast, $deleteSyncedAction, $showProgress = false)
    {
        $this->bids = $bids;
        $this->branchCode = $branchCode;
        $this->broadcast = $broadcast;
        $this->deleteSyncedAction = $deleteSyncedAction;
        $this->showProgress = $showProgress;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $syncService = app()->make(SyncService::class);

        $syncedDetails = $syncService->sync($this->bids, $this->branchCode, $this->broadcast, $this->deleteSyncedAction, $this->showProgress);

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
