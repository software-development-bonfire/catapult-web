<?php

namespace App\Jobs\CDIS;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteSynced implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bids, $branchCode, $broadcast, $perEvent,  $cancelled;
    /**
     * Create a new job instance.
     *
     * @param array $bids
     * @return void
     */
    public function __construct($bids, $branchCode, $broadcast, $perEvent = false, $cancelled = false)
    {
        $this->bids = $bids;
        $this->branchCode = $branchCode;
        $this->broadcast = $broadcast;
        $this->perEvent = $perEvent;
        $this->cancelled = $cancelled;
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
				'event' => $this->perEvent,
				'cancelled' => $this->cancelled,
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
