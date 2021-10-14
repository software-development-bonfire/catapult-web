<?php

namespace App\Services\CDIS;

use App\Entities\CDISSync;
use App\Jobs\CDIS\DeleteSynced;
use App\Jobs\CDIS\Sync;
use App\Services\ConfigurationService;
use App\Traits\DatabaseTransaction;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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

            $request = $this->request($uri, $options, $client);

            $bodyContent = json_decode($request->getBody()->getContents());

            $actionCount = 0;
            $entryCount = 0;

            if ($request->getStatusCode() == RESPONSE::HTTP_OK) {
                $actionCount = $bodyContent->data->action_count;
                $entryCount = $bodyContent->data->entry_count;
                $bids = $bodyContent->data->bids;
            }

            if ($actionCount == 0) {
                return (object) [
                    'action_count' => $actionCount,
                    'entry_count' => $entryCount
                ];
            }

            $bidsChunks = array_chunk($bids, $limit);

            foreach ($bidsChunks as $bidsChunk) {
                Sync::dispatch($bidsChunk);
            }

            return (object) [
                'action_count' => $actionCount,
                'entry_count' => $entryCount,
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

            $request = $this->request($uri, $options, $client);

            $bodyContent = json_decode($request->getBody()->getContents());

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

                $data = (array) $value->sync;

                $sync = CDISSync::where($data);

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
                    $detail = $detail->withTrashed();
                }

                $isExists = $detail->count() > 0;

                if ($isExists) {
                    if ($value->sync->action == 'delete' && $value->detail) {
                        $detail->delete();
                    } else if ($value->sync->action == 'update' || $value->sync->action == 'create') {
                        $detail->update((array) $value->detail);
                    }
                } else {
                    if ($value->detail
                        && ($value->sync->action == 'create' || $value->sync->action == 'update')
                    ) {
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

        return $response;
    }

    private function getSenderDetails() {
        return [
            'client_id' => config('configuration.client_id'),
            'product_key' => config('configuration.product_key'),
            'branch_code' => config('configuration.branch_code'),
            'system_datetime' => Carbon::now()->format('Y-m-d h:i:s'),
        ];
    }
}


