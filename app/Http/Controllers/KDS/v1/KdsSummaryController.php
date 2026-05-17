<?php

namespace App\Http\Controllers\KDS\v1;

use App\Entities\CDISKitchenStation;
use App\Entities\DeviceSettings;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
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

        $threshold = self::DELAY_THRESHOLD_MINUTES;
        $today     = now()->startOfDay();

        // ── DONE orders (today) ──────────────────────────────────────────
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

            if ($minutesTaken > $threshold) {
                $delayDoneTransactions++;
                $delayDoneItems += $itemCount;
            } else {
                $onTimeDoneTransactions++;
                $onTimeDoneItems += $itemCount;
            }
        }

        // ── ACTIVE orders (completed_at IS NULL) ─────────────────────────
        $activeQuery = KitchenDisplay::whereNull('completed_at');

        if ($stationBid) {
            $activeQuery->whereHas('details', function ($q) use ($stationBid) {
                $q->where('kitchen_station_bid', $stationBid);
            });
        }

        $onGoingTransactions      = 0;
        $onGoingItems             = 0;
        $onGoingDelayTransactions = 0;
        $onGoingDelayItems        = 0;

        foreach ($activeQuery->get() as $order) {
            $minutesElapsed = $order->transaction_date
                ? (int) now()->diffInMinutes($order->transaction_date)
                : 0;
            $itemCount = max(0, (int) ($order->total_quantity - ($order->completed_quantity ?? 0)));

            if ($minutesElapsed > $threshold) {
                $onGoingDelayTransactions++;
                $onGoingDelayItems += $itemCount;
            } else {
                $onGoingTransactions++;
                $onGoingItems += $itemCount;
            }
        }

        $ticketCountTransactions = $onGoingTransactions + $onGoingDelayTransactions;
        $ticketCountItems        = $onGoingItems        + $onGoingDelayItems;

        // ── ACTIVE items breakdown ────────────────────────────────────────
        $detailQuery = KitchenDisplayDetail::query()
            ->whereHas('head', function ($q) {
                $q->whereNull('completed_at');
            });

        if ($stationBid) {
            $detailQuery->where('kitchen_station_bid', $stationBid);
        }

        $itemsMap = [];
        foreach ($detailQuery->select('name', 'remaining_quantity', 'started_at')->get() as $detail) {
            $name = $detail->name ?? 'Unknown';
            if (!isset($itemsMap[$name])) {
                $itemsMap[$name] = ['name' => $name, 'qty' => 0, 'delay' => 0];
            }
            $itemsMap[$name]['qty'] += (int) $detail->remaining_quantity;

            $minutesElapsed = $detail->started_at
                ? (int) now()->diffInMinutes($detail->started_at)
                : 0;
            if ($minutesElapsed > $threshold) {
                $itemsMap[$name]['delay']++;
            }
        }

        return $this->successfulResponse([
            'legends' => [
                'on_time_done'   => ['transactions' => $onTimeDoneTransactions,  'items' => $onTimeDoneItems],
                'delay_done'     => ['transactions' => $delayDoneTransactions,   'items' => $delayDoneItems],
                'on_going'       => ['transactions' => $onGoingTransactions,      'items' => $onGoingItems],
                'on_going_delay' => ['transactions' => $onGoingDelayTransactions, 'items' => $onGoingDelayItems],
                'ticket_count'   => ['transactions' => $ticketCountTransactions,  'items' => $ticketCountItems],
            ],
            'items' => array_values($itemsMap),
        ], 'Summary retrieved');
    }
}
