<?php

namespace App\Services\CDIS;

use App\Entities\CDISSync;
use App\Enums\DeleteSyncedAction;
use App\Jobs\CDIS\DeleteSynced;
use App\Jobs\CDIS\Sync;
use App\Services\ConfigurationService;
use App\Traits\DatabaseTransaction;
use App\Traits\GenericHelper;
use App\Traits\JobCancellationTrait;
use App\Traits\PusherTrait;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SyncService
{
    use DatabaseTransaction, GenericHelper, PusherTrait, JobCancellationTrait;

    /**
     * Set to TRUE if you want to enable the previous syncing capability
     * By default, it's defined to FALSE to include deleted_at value
     * in syncing from CDIS database to Catapult database
     */
    private $validateDeletedToHardReset = false;

    /**
     * Get for sync bids.
     *
     * @param int $limit
     * @param string $table
     * @return \Illuminate\Http\Response
     */
    public function forSync($limit = 100, $table = 'all', $broadcast = false, $deleteSyncedDone = DeleteSyncedAction::CONVERT_ALL, $showProgress = false, $isRefetched = false)
    {
        return $this->transaction(function () use ($limit, $table, $broadcast, $deleteSyncedDone, $showProgress, $isRefetched) {
            if ($broadcast && $deleteSyncedDone === DeleteSyncedAction::CONVERT && ! $isRefetched) {
                CDISSync::truncate();
            }

            $client = [
                'verify' => false,
                'http_errors' => false,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $uri = config('endpoint.cdis.domain').''.config('endpoint.cdis.for.catapult.v1.forSync');

            $limit = $limit ? $limit : config('sync.cdis.to_catapult.limit');

            $senderDetails = $this->getSenderDetails();

            $options = [
                'json' => ['sender_details' => $senderDetails, 'limit' => $limit, 'table' => $table],
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
                Sync::dispatch($bidsChunk, $senderDetails['branch_code'], $broadcast, $deleteSyncedDone, $showProgress);
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
     * @param bool $broadcast
     * @return \Illuminate\Http\Response
     */
    public function sync($bids = [], $branchCode, $broadcast = false, $deleteSyncedDone, $showProgress = false)
    {
        if ($broadcast) {
            $this->initializePusher();
        }

        $this->setSyncing();

        return $this->transaction(function () use ($bids, $branchCode, $broadcast, $deleteSyncedDone, $showProgress) {
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

            $count = $bodyContent->data !== null && isset($bodyContent->data->count) ? $bodyContent->data->count : 0;
            $total = $bodyContent->data !== null && isset($bodyContent->data->total) ? $bodyContent->data->total : 0;
            $values = $bodyContent->data  !== null && isset($bodyContent->data->values) ? $bodyContent->data->values : array();

            if ($count == 0) {
                return (object) [
                    'count' => $count,
                    'total' => $total
                ];
            }

            $hasBeenCancelled = false;

            $bids = [];

            $progress = 0;
            foreach ($values as $value) {
                $hasBeenCancelled = $this->hasBeenCancelledSyncing();
                if ($hasBeenCancelled) {
                    break;
                }
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

                $entityInstance = app($entity);
                $entityInstance->getTableColumns();

                unset($value->detail->id);
                $syncData = (array) $value->detail;

                $data = [];

                foreach ($entityInstance->getTableColumns() as $tableColumnIndex => $tableColumn) {
                    if (isset($syncData[$tableColumn])) {
                        $data[$tableColumn] = $syncData[$tableColumn];
                    }
                }

                $detail = $entityInstance::where('bid', $value->sync->table_bid);

                $hasSoftDeleting = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($entity));

                if ($hasSoftDeleting) {
                    $detail = $detail->withTrashed();
                }

                $isExists = $detail->count() > 0;

                if ($isExists) {
                    if ($this->validateDeletedToHardReset) {
                        if ($value->sync->action == 'delete' && $value->detail) {
                            $tableName = $this->getTableName($detail);
                            if ($this->modelHasColumn($detail, $tableName, 'deleted_at')) {
                                $detail->delete();
                            } else {
                                $detail->update(['deleted_at' => null]);
                            }
                        } else if ($value->sync->action == 'update' || $value->sync->action == 'create') {
                            if (count($data) > 0) {
                                $detail->update($data);
                            }
                        }
                    } else {
                        if (count($data) > 0) {
                            $detail->update($data);
                        }
                    }
                    
                } else {
                    if ($this->validateDeletedToHardReset) {
                        if ($value->detail
                            && ($value->sync->action == 'create' || $value->sync->action == 'update')
                        ) {
                            $detail->create($data);
                        }
                    } else {
                        if ($value->detail) {
                            $detail->create($data);
                        }
                    }
                }

                $progress++;
                if ($broadcast && $showProgress) {
                    $this->pushSyncStatus($branchCode, $broadcast, __('info.syncing').$progress.' of '.$total);
                } else {
                    if ($broadcast && ($progress % 100 == 0)) {
                        $this->pushSyncStatus($branchCode,  $broadcast, __('info.syncing').$progress.' of '.$total);
                    }
                }
            }
            if ($hasBeenCancelled) {
                if ($broadcast) {
                    $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'SyncDone', __('info.syncing_cancelled'), null);
                }
            } else {
                if (count($bids) > 0) {
                    DeleteSynced::dispatch($bids, $branchCode, $broadcast, $deleteSyncedDone, $hasBeenCancelled);
                }
            }
            $this->clearCancelledSyncing();
            $this->clearSyncing();

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

    private function getSenderDetails()
    {
        return [
            'client_id' => config('configuration.client_id'),
            'product_key' => config('configuration.product_key'),
            'branch_code' => config('configuration.branch_code'),
            'system_datetime' => Carbon::now()->format('Y-m-d h:i:s'),
        ];
    }

    private function pushSyncStatus($branchCode, $broadcast, $message)
    {
        if ($broadcast) {
            $this->pusher->trigger($this->cdisAndCatapultSyncChannel($branchCode), 'Syncing', $message, null);
        }
    }

    public function getArrangedSyncableEntities()
    {
        return [
            \App\Entities\CDISBranch::class,
            \App\Entities\CDISTerminal::class,
            \App\Entities\CDISInventoryLocation::class,
            \App\Entities\CDISInventoryLocationTag::class,
            \App\Entities\CDISBranchGroup::class,
            \App\Entities\CDISBranchGroupTag::class,
            \App\Entities\CDISPaymentTermSettings::class,
            \App\Entities\CDISVendor::class,
            \App\Entities\CDISVendorBranch::class,
            \App\Entities\CDISBrand::class,
            \App\Entities\CDISUnitOfMeasurement::class,
            \App\Entities\CDISDiscountSettings::class,
            \App\Entities\CDISProductPricingType::class,
            \App\Entities\CDISProductVariant::class,
            \App\Entities\CDISProductVariantOption::class,
            \App\Entities\CDISPaymentMethodSettings::class,
            \App\Entities\CDISPaymentMethodSettingsDetail::class,
            \App\Entities\CDISOrderingDeviceSetup::class,
            \App\Entities\CDISOrderingDeviceSetupBranch::class,
            \App\Entities\CDISDisplayCategory::class,
            \App\Entities\CDISDisplayPaymentMethod::class,
            \App\Entities\CDISDisplayPaymentMethodDetail::class,
            \App\Entities\CDISDisplayPaymentMethodDeviceDisplay::class,
            \App\Entities\CDISKitchenDevicePrinter::class,
            \App\Entities\CDISKitchenDevicePrinterBranch::class,
            \App\Entities\CDISKitchenStation::class,
            \App\Entities\CDISKitchenStationProcess::class,
            \App\Entities\CDISKitchenUser::class,
            \App\Entities\CDISKitchenUserBranch::class,
            \App\Entities\CDISKitchenUserStation::class,
            \App\Entities\CDISKitchenItemSetup::class,
            \App\Entities\CDISKitchenItemSetupDetail::class,
            \App\Entities\CDISProductCategory::class,
            \App\Entities\CDISProduct::class,
            \App\Entities\CDISProductUomPackaging::class,
            \App\Entities\CDISProductBranchAvailability::class,
            \App\Entities\CDISPackagingVendor::class,
            \App\Entities\CDISCostAndPriceChange::class,
            \App\Entities\CDISCostAndPriceChangeDetail::class,
            \App\Entities\CDISProductBranchPrice::class,
            \App\Entities\CDISPackagingVendorBranchCost::class,
            \App\Entities\CDISProductAddon::class,
            \App\Entities\CDISProductAddonDetail::class,
            \App\Entities\CDISProductStructure::class,
            \App\Entities\CDISProductStructureDetail::class,
            \App\Entities\CDISProductModifier::class,
            \App\Entities\CDISProductModifierDetail::class,
            \App\Entities\CDISTags::class,
            \App\Entities\CDISProductUomPackagingTag::class,
        ];
    }
}
