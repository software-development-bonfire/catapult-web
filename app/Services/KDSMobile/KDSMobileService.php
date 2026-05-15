<?php

namespace App\Services\KDSMobile;

use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Events\KDSMobile\KDSMobileUpdateEvent;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use Illuminate\Support\Facades\DB;

class KDSMobileService
{
    /**
     * Get transactions for the mobile monitor with filters.
     */
    public function getTransactions(array $filters)
    {
        $query = KitchenDisplay::with('details')
            ->whereNull('deleted_at');

        // Branch filter (terminal_bid maps to branch devices)
        if (!empty($filters['branch_bid'])) {
            $terminalBids = DB::table('cdis_terminal')
                ->where('branch_bid', $filters['branch_bid'])
                ->pluck('bid')
                ->toArray();
            $query->whereIn('terminal_bid', $terminalBids);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'on_time_done':
                    $query->whereNotNull('completed_at')
                        ->whereRaw('completed_at <= DATE_ADD(created_at, INTERVAL (SELECT COALESCE(MAX(max_prep_time), 30) FROM cdis_kitchen_item_setup_detail) MINUTE)');
                    break;
                case 'delay':
                    $query->whereNull('completed_at')
                        ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL ? MINUTE)', [$filters['delay_minutes'] ?? 10]);
                    break;
                case 'on_going':
                    $query->whereNull('completed_at')
                        ->whereRaw('NOW() <= DATE_ADD(created_at, INTERVAL ? MINUTE)', [$filters['delay_minutes'] ?? 10]);
                    break;
                case 'on_going_delay':
                    $query->whereNull('completed_at')
                        ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL ? MINUTE)', [$filters['on_going_delay_minutes'] ?? 5]);
                    break;
            }
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return $this->formatTransactions($transactions, $filters);
    }

    /**
     * Get a single transaction with full detail.
     */
    public function getTransactionDetail($transactionId)
    {
        $kitchenDisplay = KitchenDisplay::with('details')
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$kitchenDisplay) {
            return null;
        }

        return $this->formatSingleTransaction($kitchenDisplay);
    }

    /**
     * Get summary counts for tabs.
     */
    public function getSummary(array $filters)
    {
        $baseQuery = KitchenDisplay::whereNull('deleted_at');

        if (!empty($filters['branch_bid'])) {
            $terminalBids = DB::table('cdis_terminal')
                ->where('branch_bid', $filters['branch_bid'])
                ->pluck('bid')
                ->toArray();
            $baseQuery->whereIn('terminal_bid', $terminalBids);
        }

        if (!empty($filters['date_from'])) {
            $baseQuery->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQuery->where('created_at', '<=', $filters['date_to']);
        }

        $delayMinutes = $filters['delay_minutes'] ?? 10;
        $onGoingDelayMinutes = $filters['on_going_delay_minutes'] ?? 5;

        $all = (clone $baseQuery)->count();

        $onTimeDone = (clone $baseQuery)
            ->whereNotNull('completed_at')
            ->count();

        $delay = (clone $baseQuery)
            ->whereNull('completed_at')
            ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL ? MINUTE)', [$delayMinutes])
            ->count();

        $onGoing = (clone $baseQuery)
            ->whereNull('completed_at')
            ->whereRaw('NOW() <= DATE_ADD(created_at, INTERVAL ? MINUTE)', [$delayMinutes])
            ->count();

        $onGoingDelay = (clone $baseQuery)
            ->whereNull('completed_at')
            ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL ? MINUTE)', [$onGoingDelayMinutes])
            ->whereRaw('NOW() <= DATE_ADD(created_at, INTERVAL ? MINUTE)', [$delayMinutes])
            ->count();

        return [
            'all' => $all,
            'on_time_done' => $onTimeDone,
            'delay' => $delay,
            'on_going' => $onGoing,
            'on_going_delay' => $onGoingDelay,
        ];
    }

    /**
     * Get available branches for the monitor.
     */
    public function getBranches()
    {
        $branches = DB::table('cdis_branch')
            ->whereNull('deleted_at')
            ->select('bid', 'name', 'code')
            ->orderBy('name')
            ->get();

        return $branches->map(function ($branch) {
            return [
                'bid' => (string) $branch->bid,
                'name' => $branch->name,
                'code' => $branch->code,
            ];
        })->toArray();
    }

    /**
     * Broadcast update notification to all mobile monitors.
     */
    public function broadcastUpdate($branchBid, $action, $transactionId = null, $orderNumber = null)
    {
        broadcast(new KDSMobileUpdateEvent($branchBid, $action, $transactionId, $orderNumber));
    }

    /**
     * Format transactions for the API response.
     */
    private function formatTransactions($transactions, array $filters)
    {
        $delayMinutes = $filters['delay_minutes'] ?? 10;
        $onGoingDelayMinutes = $filters['on_going_delay_minutes'] ?? 5;

        return $transactions->map(function ($tx) use ($delayMinutes, $onGoingDelayMinutes) {
            return $this->formatSingleTransaction($tx, $delayMinutes, $onGoingDelayMinutes);
        })->toArray();
    }

    /**
     * Format a single transaction with status classification.
     */
    private function formatSingleTransaction($kitchenDisplay, $delayMinutes = 10, $onGoingDelayMinutes = 5)
    {
        $createdAt = $kitchenDisplay->created_at;
        $completedAt = $kitchenDisplay->completed_at;
        $now = now();

        // Determine status
        $status = 'on_going';
        $delayTime = null;

        if ($completedAt) {
            $status = 'on_time_done';
        } else {
            $elapsedMinutes = $createdAt->diffInMinutes($now);
            if ($elapsedMinutes > $delayMinutes) {
                $status = 'delay';
                $delayTime = $elapsedMinutes - $delayMinutes;
            } elseif ($elapsedMinutes > $onGoingDelayMinutes) {
                $status = 'on_going_delay';
                $delayTime = $elapsedMinutes - $onGoingDelayMinutes;
            }
        }

        // Get branch name
        $branchName = DB::table('cdis_terminal')
            ->join('cdis_branch', 'cdis_terminal.branch_bid', '=', 'cdis_branch.bid')
            ->where('cdis_terminal.bid', $kitchenDisplay->terminal_bid)
            ->value('cdis_branch.name') ?? 'Unknown';

        // Format items
        $items = $kitchenDisplay->details->map(function ($detail) {
            $stationName = null;
            if ($detail->kitchen_station_bid) {
                $stationName = DB::table('cdis_kitchen_station')
                    ->where('bid', $detail->kitchen_station_bid)
                    ->value('name');
            }

            return [
                'bid' => (string) $detail->bid,
                'name' => $detail->name,
                'quantity' => (float) $detail->remaining_quantity,
                'status' => $detail->status,
                'kitchen_station_bid' => $detail->kitchen_station_bid ? (string) $detail->kitchen_station_bid : null,
                'kitchen_station_name' => $stationName,
                'is_addon' => (bool) $detail->is_addon,
                'addons' => $detail->addons,
                'special_request' => $detail->special_request,
                'usage_type' => $detail->usage_type,
                'order_type_name' => $detail->order_type_name,
            ];
        })->toArray();

        return [
            'bid' => (string) $kitchenDisplay->bid,
            'transaction_id' => $kitchenDisplay->transaction_id,
            'terminal_number' => $kitchenDisplay->terminal_number,
            'transaction_date' => $kitchenDisplay->transaction_date,
            'total_quantity' => (float) $kitchenDisplay->total_quantity,
            'completed_quantity' => (float) $kitchenDisplay->completed_quantity,
            'created_at' => $kitchenDisplay->created_at ? $kitchenDisplay->created_at->toISOString() : null,
            'completed_at' => $completedAt ? $completedAt->toISOString() : null,
            'status' => $status,
            'delay_time' => $delayTime,
            'branch_name' => $branchName,
            'items' => $items,
        ];
    }
}
