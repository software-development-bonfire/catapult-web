<?php

namespace App\Services\CDIS;

use App\Entities\CDISSync;
use App\Jobs\CDIS\DeleteSynced;
use App\Jobs\CDIS\Sync;
use App\Traits\DatabaseTransaction;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class SyncService
{
    use DatabaseTransaction;

    /**
     * Get for sync bids.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function forSync()
    {
        return $this->transaction(function () {
            $client = [
                'verify' => false,
                'http_errors' => false,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $uri = config('endpoint.cdis.domain').''.config('endpoint.cdis.for.catapult.v1.forSync');
            $limit = config('sync.cdis.to_catapult.limit');

            $options = [
                'json' => ['sender_details' => $this->getSenderDetails(), 'limit' => $limit],
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ];

            $bodyContent = $this->request($uri, $options, $client);

            $count = $bodyContent->data->count;
            $total = $bodyContent->data->total;
            $bids = $bodyContent->data->bids;

            if ($count == 0) {
                return (object) [
                    'count' => $count,
                    'total' => $total
                ];
            }

            $bidsChunks = array_chunk($bids, $limit);

            foreach ($bidsChunks as $bidsChunk) {
                Sync::dispatch($bidsChunk);
            }

            return (object) [
                'count' => $count,
                'total' => $total,
                'bidsChunks' => $bidsChunks,
            ];
        });
    }

    /**
     * Sync details and data.
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function sync($bids = [])
    {
        return $this->transaction(function () use ($bids) {
            $client = [
                'verify' => false,
                'http_errors' => false,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $uri = config('endpoint.cdis.domain').''.config('endpoint.cdis.for.catapult.v1.sync');

            $options = [
                'json' => ['sender_details' => $this->getSenderDetails(), 'bids' => $bids],
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ];

            $bodyContent = $this->request($uri, $options, $client);

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
                $entity = "App\\Entities\\CDIS".$entityName;

                $detail = app($entity)::where('bid', $value->sync->table_bid);

                $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity));

                if ($hasSoftDeleting) {
                    $detail->withTrashed();
                }

                $isExists = $detail->count() > 0;

                if ($isExists) {
                    if ($value->sync->action == 'delete') {
                        $detail->delete();
                    } else if ($value->sync->action == 'update' || $value->sync->action == 'create'){
                        $detail->update((array) $value->detail);
                    }
                } else {
                    if ($value->sync->action == 'create' || $value->sync->action == 'update') {
                        $detail->create((array) $value->detail);
                    }
                }
            }

            if (count($bids) > 0) {
                DeleteSynced::dispatch($bids);
            }

            return (object) [
                'count' => $count,
                'total' => $total,
                'bids' => $bids,
            ];
        });
    }

    public function request($uri, $options, $clientConfig, $method = 'POST')
    {
        $client = new Client($clientConfig);
        $response = $client->request($method, $uri, $options);

        return json_decode($response->getBody()->getContents());
    }

    private function getSenderDetails() {
//        $configuration = app()->make(ConfigurationService::class);

        return [
//            'client_id' => $configuration->getAttributeValue('client_id'),
//            'product_key' => $configuration->getAttributeValue('product_key'),
//            'machine_uuid' => $configuration->getAttributeValue('machine_uuid'),
//            'system_datetime' => Carbon::now()->format('Y-m-d h:i:s'),
        ];
    }
}


