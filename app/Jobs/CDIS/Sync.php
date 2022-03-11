<?php

namespace App\Jobs\CDIS;

use App\Services\CDIS\SyncService;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Sync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bids, $branchCode, $broadcast;
    /**
     * Create a new job instance.
     *
     * @param array $bids
     * @return void
     */
    public function __construct($bids, $branchCode, $broadcast)
    {
        $this->bids = $bids;
        $this->branchCode = $branchCode;
        $this->broadcast = $broadcast;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $syncService = app()->make(SyncService::class);

        $syncedDetails = $syncService->sync($this->bids, $this->branchCode, $this->broadcast);

        return $syncedDetails;
    }
}
