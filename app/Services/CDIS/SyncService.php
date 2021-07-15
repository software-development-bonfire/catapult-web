<?php

namespace App\Services\CDIS;

use App\Entities\CDISSync;
use App\Jobs\CDIS\DeleteSynced;
use App\Services\ConfigurationService;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class SyncService
{
    use DatabaseTransaction;

    /**
     * For sync.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function forSync()
    {
        return $this->transaction(function () {
            $bodyContent = $this->getForSync();

            $count = $bodyContent->data->count;
            $total = $bodyContent->data->total;
            $values = $bodyContent->data->values;

            if ($count == 0) {
                return (object) [
                    'count' => $count,
                    'total' => $total
                ];
            }

            $bids = [];

            foreach ($values as $value) {
                array_push($bids, $value->sync->bid);

                unset($value->sync->id);
                unset($value->sync->bid);

                $sync = CDISSync::where([
                    'branch_bid' => $value->sync->branch_bid,
                    'table_bid' => $value->sync->table_bid,
                    'table_name' => $value->sync->table_name,
                    'level' => $value->sync->level,
                    'group' => $value->sync->group,
                    'code' => $value->sync->code,
                    'action' => $value->sync->action,

                ]);

                if ($sync->exists()) {
                    $sync->update((array) $value->sync);
                } else {
                    $sync->create((array) $value->sync);
                }

                $entityName = str_replace('_', '', Str::title($value->sync->table_name));
                app("App\\Entities\\CDIS" . $entityName)::create((array) $value->detail);
            }

            DeleteSynced::dispatch($bids)->onQueue('delete-synced');

            return (object) [
                'count' => $count,
                'total' => $total,
                'bids' => $bids,
            ];
        });
    }

    public function getForSync()
    {
//        $configuration = app()->make(ConfigurationService::class);

        $senderDetails = [
//            'client_id' => $configuration->getAttributeValue('client_id'),
//            'product_key' => $configuration->getAttributeValue('product_key'),
//            'machine_uuid' => $configuration->getAttributeValue('machine_uuid'),
//            'system_datetime' => Carbon::now()->format('Y-m-d h:i:s'),
        ];

        $client = new Client([
            'verify' => false,
            'http_errors' => false,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $options = [
            'json' => ['sender_details' => $senderDetails, 'chunk' => 1],
            'headers' => [
//                'Authorization' => 'Bearer '. $configuration->getAttributeValue('bearer_token'),
                'Accept' => 'application/json',
            ]
        ];

        $uri = config('endpoint.cdis.domain').''.config('endpoint.cdis.for.catapult.v1.forSync');

        $response = $client->request('POST', $uri, $options);

        $responseBodyContent = json_decode($response->getBody()->getContents());

        return $responseBodyContent;
    }
}


