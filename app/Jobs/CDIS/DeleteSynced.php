<?php

namespace App\Jobs\CDIS;

use App\Enums\DeleteSyncedAction;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteSynced implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bids, $branchCode, $broadcast, $catapultActionType, $cancelled, $resummable;
    /**
     * Create a new job instance.
     *
     * @param array $bids
     * @return void
     */
    public function __construct($bids, $branchCode, $broadcast, $catapultActionType, $cancelled = false, $resummable = false)
    {
        $this->bids = $bids;
        $this->branchCode = $branchCode;
        $this->broadcast = $broadcast;
        $this->catapultActionType = $catapultActionType;
        $this->cancelled = $cancelled;
        $this->resummable = $resummable;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new Client([
            'verify' => false,
            'http_errors' => false,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $options = [
            'json' => [
                'bid' => $this->bids,
                'branch_code' => $this->branchCode,
                'broadcast' => $this->broadcast,
				'catapultActionType' => $this->catapultActionType,
				'cancelled' => $this->cancelled,
				'resummable' => $this->resummable,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        $uri = config('endpoint.cdis.domain').''.config('endpoint.cdis.for.catapult.v1.deleteSynced');

        $response = $client->request('DELETE', $uri, $options);

        $responseBodyContent = json_decode($response->getBody()->getContents());

        return $responseBodyContent;
    }
}
