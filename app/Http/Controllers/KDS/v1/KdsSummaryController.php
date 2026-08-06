<?php

namespace App\Http\Controllers\KDS\v1;

use App\Entities\CDISKitchenStation;
use App\Entities\DeviceSettings;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\KDSActionType;
use App\Enums\KDS\MenuStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KdsSummaryController extends Controller
{
    const DELAY_THRESHOLD_MINUTES = 5;

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

        // ACTIVE items breakdown
        $detailQuery = KitchenDisplayDetail::query()
            ->whereHas('head', function ($q) use ($stationBid) {
                $q->whereNull('completed_at')
                ;//->where('action_type', '!=', KDSActionType::FOR_PREPARE);
                if ($stationBid) {
                    $q->where('kitchen_station_bid', $stationBid);
                }
            });

        $itemsMap = [];
        $minutesElapsed = 0;
        foreach ($detailQuery->select(
            'name', 
            'remaining_quantity', 
            'started_at', 
            'prepared_at', 
            'bumped_at' , 
            'served_at' , 
            'action_type',
            'prepared_quantity',
            'bumped_quantity',
            'released_quantity'
            )->get() as $detail) {
            $name = $detail->name ?? 'Unknown';
            if (!isset($itemsMap[$name])) {
                $itemsMap[$name] = ['name' => $name, 'qty' => 0, 'delay' => 0];
            }
            if ($detail->action_type == KDSActionType::FOR_PREPARE) {
                $itemsMap[$name]['qty'] += (int) $detail->remaining_quantity;

                $minutesElapsed = $detail->sent_at
                    ? (int) now()->diffInMinutes($detail->sent_at)
                    : 0;


                if ($minutesElapsed > $detail->max_waiting_time ?? 60) {
                    $itemsMap[$name]['delay']++;
                }
            } else  if ($detail->action_type == KDSActionType::FOR_BUMP) {
                $itemsMap[$name]['qty'] += (int) $detail->prepared_quantity;

                $minutesElapsed = $detail->prepared_at
                    ? (int) now()->diffInMinutes($detail->prepared_at)
                    : 0;

                if ($minutesElapsed > $detail->max_waiting_time ?? 60) {
                    $itemsMap[$name]['delay']++;
                }
            } else if ($detail->action_type == KDSActionType::FOR_SERVE) {
                $itemsMap[$name]['qty'] += (int) $detail->released_quantity;

                $minutesElapsed = $detail->bumped_at
                    ? (int) now()->diffInMinutes($detail->bumped_at)
                    : 0;

                    
                if ($minutesElapsed > $detail->max_serving_time ?? 60) {
                    $itemsMap[$name]['delay']++;
                }
            } else {
                $itemsMap[$name]['qty'] += (int) $detail->released_quantity;

                $minutesElapsed = $detail->served_at
                    ? (int) now()->diffInMinutes($detail->served_at)
                    : 0;

                     $itemsMap[$name]['qty'] += 0;
            }
           

        }

        $items = array_values($itemsMap);
        usort($items, function ($a, $b) {
            return (int)$b['qty'] <=> (int)$a['qty'];
        });
        return $this->successfulResponse([
            'order_summary' => [
                'total_serve' => ['transactions' => $onTimeDoneTransactions + $delayDoneTransactions,  'items' => $onTimeDoneItems + $delayDoneItems],
                'serving' => ['transactions' => $onGoingTransactions + $onGoingDelayTransactions,  'items' => $onGoingItems + $onGoingDelayItems],
            ],
            'legends' => [
                'on_time_done'   => ['transactions' => $onTimeDoneTransactions,  'items' => $onTimeDoneItems],
                'delay_done'     => ['transactions' => $delayDoneTransactions,   'items' => $delayDoneItems],
                'on_going'       => ['transactions' => $onGoingTransactions,      'items' => $onGoingItems],
                'on_going_delay' => ['transactions' => $onGoingDelayTransactions, 'items' => $onGoingDelayItems],
                // 'ticket_count'   => ['transactions' => $ticketCountTransactions,  'items' => $ticketCountItems],
            ],
            'items' => $items,
        ], 'Summary retrieved');
    }
}
