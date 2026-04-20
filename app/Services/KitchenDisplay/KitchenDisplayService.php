<?php

namespace App\Services\KitchenDisplay;

use App\Entities\CDISTerminal;
use App\Entities\CDISTerminalTransaction;
use App\Entities\DeviceSettings;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Entities\KitchenDisplayMovementHistory;
use App\Enums\KDS\MenuStatus;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Traits\DatabaseTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Abstract Base Service for Kitchen Display
 * 
 * Defines common operations for both Fast-Food and Fine-Dining orders
 * Specific implementations extend this class for order-type-specific logic
 */
abstract class KitchenDisplayService
{
    use DatabaseTransaction;

    /**
     * System mode this service handles
     * Must be overridden in subclasses
     */
    protected $systemMode = 'UNKNOWN'; // 'FASTFOOD' or 'FINEDINE'

    /**
     * Movement strategy for this order type
     */
    protected $movementStrategy;

    /**
     * Create or update kitchen display order
     * 
     * @param array $transactionData
     * @return KitchenDisplay|null
     */
    abstract public function storeOrder(array $transactionData): ?KitchenDisplay;

    /**
     * Move item to next/previous station
     * Calls movement strategy implementation
     * 
     * @param array $itemData
     * @return bool
     */
    abstract public function moveItem(array $itemData): bool;

    /**
     * Release item from kitchen (mark ready for pickup)
     * 
     * @param array $itemData
     * @return bool
     */
    abstract public function releaseItem(array $itemData): bool;

    /**
     * Mark item as done/complete
     * 
     * @param array $itemData
     * @return bool
     */
    abstract public function doneItem(array $itemData): bool;

    /**
     * Mark entire order as done
     * 
     * @param array $orderData
     * @return bool
     */
    abstract public function doneOrder(array $orderData): bool;

    /**
     * Remove item from kitchen display
     * 
     * @param array $itemData
     * @return bool
     */
    abstract public function removeItem(array $itemData): bool;

    /**
     * Remove entire order from kitchen display
     * 
     * @param array $orderData
     * @return bool
     */
    abstract public function removeOrder(array $orderData): bool;

    /**
     * Get order details with all items and status
     * 
     * @param string $orderId
     * @return array|null
     */
    public function getOrderDetails(string $orderId): ?array
    {
        $kitchenDisplay = KitchenDisplay::where('bid', $orderId)->first();
        
        if (!$kitchenDisplay) {
            return null;
        }

        $details = KitchenDisplayDetail::where('head_bid', $kitchenDisplay->bid)->get();

        return [
            'order' => $kitchenDisplay->toArray(),
            'items' => $details->map(function ($detail) {
                return [
                    'bid' => $detail->bid,
                    'name' => $detail->name,
                    'quantity' => $detail->remaining_quantity,
                    'current_station' => $detail->current_station_index,
                    'status' => $detail->status,
                    'station_sequence' => json_decode($detail->station_sequence, true),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get orders at specific station
     * 
     * @param int $stationIndex (1-4 for kitchen, 0 for releasing)
     * @param string|null $terminalBid
     * @return array
     */
    public function getOrdersByStation(int $stationIndex, ?string $terminalBid = null): array
    {
        $query = KitchenDisplayDetail::where('current_station_index', $stationIndex)
            ->where('status', MenuStatus::ON_PROCESS);

        if ($terminalBid) {
            $query->whereHas('kitchenDisplay', function ($q) use ($terminalBid) {
                $q->where('terminal_bid', $terminalBid);
            });
        }

        $items = $query->get();

        // Group by head_bid (order)
        $groupedByOrder = [];
        foreach ($items as $item) {
            $headBid = $item->head_bid;
            if (!isset($groupedByOrder[$headBid])) {
                $kitchenDisplay = KitchenDisplay::find($headBid);
                $groupedByOrder[$headBid] = [
                    'order' => $kitchenDisplay->toArray(),
                    'items' => [],
                ];
            }
            $groupedByOrder[$headBid]['items'][] = $item->toArray();
        }

        return array_values($groupedByOrder);
    }

    /**
     * Record movement in history for debugging
     * 
     * @param string $detailBid
     * @param int $fromStation
     * @param int $toStation
     * @param int $quantity
     * @param string $movementType
     * @return void
     */
    protected function recordMovement(
        string $detailBid,
        int $fromStation,
        int $toStation,
        int $quantity,
        string $movementType = 'FORWARD'
    ): void {
        $detail = KitchenDisplayDetail::find($detailBid);

        if (!$detail) {
            Log::warning("KitchenDisplayMovementHistory: Detail not found: {$detailBid}");
            return;
        }

        KitchenDisplayMovementHistory::create([
            'detail_bid' => $detailBid,
            'from_station_index' => $fromStation,
            'to_station_index' => $toStation,
            'quantity_moved' => $quantity,
            'movement_type' => $movementType,
            'status_before' => $detail->status,
            'status_after' => $detail->status,
        ]);
    }

    /**
     * Get movement history for an item
     * 
     * @param string $detailBid
     * @return array
     */
    public function getMovementHistory(string $detailBid): array
    {
        return KitchenDisplayMovementHistory::where('detail_bid', $detailBid)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Update station indices after station changes
     * 
     * @param string $headBid
     * @param int $fromStation
     * @param int $toStation
     * @return void
     */
    protected function updateStationIndices(string $headBid, int $fromStation, int $toStation): void
    {
        // Get all items at from_station
        $items = KitchenDisplayDetail::where('head_bid', $headBid)
            ->where('current_station_index', $fromStation)
            ->get();

        foreach ($items as $item) {
            $item->update(['current_station_index' => $toStation]);
        }
    }

    /**
     * Log service action
     * 
     * @param string $action
     * @param array $data
     * @return void
     */
    protected function log(string $action, array $data = []): void
    {
        Log::info(
            sprintf(
                '%s::%s',
                class_basename(static::class),
                $action
            ),
            $data
        );
    }

    /**
     * Get next station in sequence for item
     * 
     * @param KitchenDisplayDetail $detail
     * @return int|null
     */
    protected function getNextStationInSequence(KitchenDisplayDetail $detail): ?int
    {
        $sequence = json_decode($detail->station_sequence, true);

        if (!is_array($sequence)) {
            return null;
        }

        $currentPosition = $detail->current_position_in_sequence ?? 0;
        $nextPosition = $currentPosition + 1;

        return $sequence[$nextPosition] ?? null;
    }

    /**
     * Get previous station in sequence for item
     * 
     * @param KitchenDisplayDetail $detail
     * @return int|null
     */
    protected function getPreviousStationInSequence(KitchenDisplayDetail $detail): ?int
    {
        $sequence = json_decode($detail->station_sequence, true);

        if (!is_array($sequence) || empty($sequence)) {
            return null;
        }

        $currentPosition = $detail->current_position_in_sequence ?? 0;
        $previousPosition = $currentPosition - 1;

        return $sequence[$previousPosition] ?? null;
    }

    /**
     * Resolve device UID from a kitchen station bid via device_settings
     * 
     * @param string|null $kitchenStationBid
     * @return string|null
     */
    protected function getDeviceUidForStation(?string $kitchenStationBid): ?string
    {
        if (!$kitchenStationBid) {
            return null;
        }

        return DeviceSettings::where('kitchen_station_bid', $kitchenStationBid)
            ->where('device_type', 'KDS')
            ->value('device_uid');
    }

    /**
     * Get all unique device UIDs for items belonging to an order
     * 
     * @param string $headBid
     * @return array
     */
    protected function getDeviceUidsForOrder(string $headBid): array
    {
        $stationBids = KitchenDisplayDetail::where('head_bid', $headBid)
            ->whereNotNull('kitchen_station_bid')
            ->pluck('kitchen_station_bid')
            ->unique()
            ->toArray();

        if (empty($stationBids)) {
            return [];
        }

        return DeviceSettings::whereIn('kitchen_station_bid', $stationBids)
            ->where('device_type', 'KDS')
            ->pluck('device_uid')
            ->unique()
            ->filter()
            ->toArray();
    }
}
