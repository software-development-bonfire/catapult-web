<?php

namespace App\Http\Controllers\KDS\v1;

use App\Entities\CDISKitchenStation;
use App\Entities\DeviceSettings;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\KDSActionType;
use App\Enums\KDS\MenuStatus;
use App\Enums\KDS\QueueingGroup;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KdsSummaryController extends Controller
{
    const DELAY_THRESHOLD_MINUTES = 5;

    const DEFAULT_MAX_MINUTES = 60;

    /**
     * Per action type: which column holds the quantity still pending on that
     * stage, the timestamp the stage started from, and the SLA column.
     * FOR_RECALL is intentionally excluded — recalled rows are never counted.
     */
    const STAGE_MAP = [
        KDSActionType::FOR_PREPARE  => ['quantity' => 'remaining_quantity', 'since' => 'sent_at',      'limit' => 'max_waiting_time'],
        KDSActionType::FOR_BUMP     => ['quantity' => 'prepared_quantity',  'since' => 'prepared_at',  'limit' => 'max_preparation_time'],
        KDSActionType::FOR_ASSEMBLY => ['quantity' => 'bumped_quantity',    'since' => 'bumped_at',    'limit' => 'max_assembly_time'],
        KDSActionType::FOR_SERVE    => ['quantity' => 'assembled_quantity', 'since' => 'assembled_at', 'limit' => 'max_serving_time'],
    ];

    /**
     * Return KDS summary data: legend counts and active item breakdown.
     *
     * Request params (all optional):
     *   device_uid   – filter to the station bound to this device
     *   station_code – filter to a specific station code (ignored when device_uid resolves a station)
     */
    public function summary(Request $request): JsonResponse
    {
        $deviceUid   = $request->get('device_uid');
        $stationCode = $request->get('station_code');

        // Resolve station BID from station_code, or fall back to device_uid
        $stationBid = null;
        if ($stationCode) {
            $stationBid = CDISKitchenStation::where('code', $stationCode)->value('bid');
        } elseif ($deviceUid) {
            $stationBid = DeviceSettings::where('device_uid', $deviceUid)
                ->whereNotNull('kitchen_station_bid')
                ->value('kitchen_station_bid');
        }

        // Determine if the stationBid is queue_group_type is PREPARING, FINISHING, RELEASING or BAR Device
        $station = CDISKitchenStation::where('bid', $stationBid)->first();
        $isBarDevice = false;
        $isPreparingDevice = false;
        $isFinishingDevice = false;
        $isReleasingDevice = false;
        $barStationNameIdentifiers = ['BAR', 'BEVERAGE', 'BEVERAGES'];
        if ($station) {
            $queueGroupType = $station->queue_group_type;
            $isBarDevice = in_array(strtoupper(trim($station->name)), $barStationNameIdentifiers, true);
            $isPreparingDevice = $queueGroupType === QueueingGroup::PREPARING;
            $isFinishingDevice = $queueGroupType === QueueingGroup::FINISHING;
            $isReleasingDevice = $queueGroupType === QueueingGroup::RELEASING;
        }

        $today     = now()->startOfDay();

        // DONE orders (today)
        $doneQuery = KitchenDisplay::whereNotNull('completed_at')
            ->where('completed_at', '>=', $today);

        if ($stationBid) {
            // Details are soft-deleted when an order is done, so use withTrashed
            $doneQuery->whereHas('details', function ($q) use ($stationBid) {
                $q->withTrashed()->where('kitchen_station_bid', $stationBid);
            });
        }

        $onTimeDoneTransactions = 0;
        $onTimeDoneItems        = 0;
        $delayDoneTransactions  = 0;
        $delayDoneItems         = 0;

        foreach ($doneQuery->get() as $order) {
            $minutesTaken = $order->transaction_date
                ? (int) $order->completed_at->diffInMinutes($order->transaction_date)
                : 0;
            $itemCount = (int) ($order->completed_quantity ?? $order->total_quantity);

            if ($minutesTaken > $order->max_preparation_time ?? 60) {
                $delayDoneTransactions++;
                $delayDoneItems += $itemCount;
            } else {
                $onTimeDoneTransactions++;
                $onTimeDoneItems += $itemCount;
            }
        }

        // ACTIVE orders (completed_at IS NULL)
        $activeQuery = KitchenDisplay::whereNull('completed_at')
            ->whereHas('details', function ($q) use ($stationBid) {
                $q->where('action_type', '!=', KDSActionType::FOR_PREPARE);
                if ($stationBid) {
                    $q->where('kitchen_station_bid', $stationBid);
                }
            });

        $onGoingTransactions      = 0;
        $onGoingItems             = 0;
        $onGoingDelayTransactions = 0;
        $onGoingDelayItems        = 0;

        foreach ($activeQuery->get() as $order) {

            $minutesElapsed = $order->transaction_date
                ? (int) now()->diffInMinutes($order->transaction_date)
                : 0;
            $itemCount = max(0, (int) ($order->total_quantity - ($order->completed_quantity ?? 0)));

            if ($minutesElapsed > $order->max_preparation_time ?? 60) {
                $onGoingDelayTransactions++;
                $onGoingDelayItems += $itemCount;
            } else {
                $onGoingTransactions++;
                $onGoingItems += $itemCount;
            }
        }

        $ticketCountTransactions = $onGoingTransactions + $onGoingDelayTransactions;
        $ticketCountItems        = $onGoingItems        + $onGoingDelayItems;

        // ACTIVE items breakdown — only the stages this device is responsible for are counted
        $countableActionTypes = $this->countableActionTypes(
            $isBarDevice,
            $isPreparingDevice,
            $isReleasingDevice
        );

        $activeDetails = KitchenDisplayDetail::query()
            ->whereIn('action_type', $countableActionTypes)
            ->when($stationBid, function ($q) use ($stationBid) {
                $q->where('kitchen_station_bid', $stationBid);
            })
            ->whereHas('head', function ($q) {
                $q->whereNull('completed_at');
            })
            ->select([
                'transaction_id',
                'terminal_number',
                'name',
                'action_type',
                'is_additional',
                'remaining_quantity',
                'prepared_quantity',
                'bumped_quantity',
                'assembled_quantity',
                'released_quantity',
                'sent_at',
                'prepared_at',
                'bumped_at',
                'assembled_at',
                'max_waiting_time',
                'max_preparation_time',
                'max_assembly_time',
                'max_serving_time',
            ])
            ->get();

        $itemsMap             = [];
        $additionalItemsMap   = [];
        $additionalTxnMap     = [];

        foreach ($activeDetails as $detail) {
            $stage = self::STAGE_MAP[(int) $detail->action_type] ?? null;
            if (!$stage) {
                continue;
            }

            $qty = (int) $detail->{$stage['quantity']};
            if ($qty <= 0) {
                continue;
            }

            $limit   = (float) ($detail->{$stage['limit']} ?: self::DEFAULT_MAX_MINUTES);
            $since   = $detail->{$stage['since']};
            $elapsed = $since ? (int) now()->diffInMinutes($since) : 0;
            $delayed = $elapsed > $limit ? $qty : 0;

            $name = $detail->name ?? 'Unknown';
            if (!isset($itemsMap[$name])) {
                $itemsMap[$name] = ['name' => $name, 'qty' => 0, 'delay' => 0];
            }
            $itemsMap[$name]['qty']   += $qty;
            $itemsMap[$name]['delay'] += $delayed;

            if (!$detail->is_additional) {
                continue;
            }

            // This section handles additional items, which are items marked as additional in the order.
            if (!isset($additionalItemsMap[$name])) {
                $additionalItemsMap[$name] = ['name' => $name, 'qty' => 0, 'delay' => 0];
            }
            $additionalItemsMap[$name]['qty']   += $qty;
            $additionalItemsMap[$name]['delay'] += $delayed;

            $transactionId = (string) $detail->transaction_id;
            if (!isset($additionalTxnMap[$transactionId])) {
                $additionalTxnMap[$transactionId] = [
                    'transaction_id'  => $transactionId,
                    'terminal_number' => $detail->terminal_number,
                    'items'           => 0,
                    'qty'             => 0,
                    'delay'           => 0,
                ];
            }
            $additionalTxnMap[$transactionId]['items']++;
            $additionalTxnMap[$transactionId]['qty']   += $qty;
            $additionalTxnMap[$transactionId]['delay'] += $delayed;
        }

        $sortByQtyDesc = function ($a, $b) {
            return (int) $b['qty'] <=> (int) $a['qty'];
        };

        $items = array_values($itemsMap);
        usort($items, $sortByQtyDesc);

        $additionalItems = array_values($additionalItemsMap);
        usort($additionalItems, $sortByQtyDesc);

        $additionalTransactions = array_values($additionalTxnMap);
        usort($additionalTransactions, $sortByQtyDesc);

        return $this->successfulResponse([
            'order_summary' => [
                'total_serve' => ['transactions' => $onTimeDoneTransactions + $delayDoneTransactions,  'items' => $onTimeDoneItems + $delayDoneItems],
                'serving' => ['transactions' => $onGoingTransactions + $onGoingDelayTransactions,  'items' => $onGoingItems + $onGoingDelayItems],
            ],
            'legends' => [
                'on_time_done'   => ['transactions' => $onTimeDoneTransactions,  'items' => $onTimeDoneItems],
                'near_beyond'    => ['transactions' => $delayDoneTransactions,   'items' => $delayDoneItems],
                'delay_done'     => ['transactions' => $delayDoneTransactions,   'items' => $delayDoneItems],
                'on_going'       => ['transactions' => $onGoingTransactions,      'items' => $onGoingItems],
                'on_going_delay' => ['transactions' => $onGoingDelayTransactions, 'items' => $onGoingDelayItems],
                // 'ticket_count'   => ['transactions' => $ticketCountTransactions,  'items' => $ticketCountItems],
            ],
            'items' => $items,
            'additional' => [
                'transactions'       => $additionalTransactions,
                'items'              => $additionalItems,
                'total_additional_transactions' => count($additionalTransactions),
                'total_additional_items'        => array_sum(array_column($additionalItems, 'qty')),
            ],
        ], 'Summary retrieved');
    }

    /**
     * Action types whose pending quantities are counted for the requesting device.
     */
    private function countableActionTypes(bool $isBarDevice, bool $isPreparingDevice, bool $isReleasingDevice): array
    {
        if ($isBarDevice) {
            return array_keys(self::STAGE_MAP);
        }

        if ($isPreparingDevice) {
            return [KDSActionType::FOR_PREPARE, KDSActionType::FOR_BUMP];
        }

        if ($isReleasingDevice) {
            return [KDSActionType::FOR_SERVE, KDSActionType::FOR_ASSEMBLY];
        }

        return array_keys(self::STAGE_MAP);
    }
}
