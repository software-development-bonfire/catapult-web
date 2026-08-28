<?php

namespace App\Repositories\TMG;

use App\Entities\CDISKitchenStation;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TmgRepository
{
    public function getKitchenStation(string $bid): ?array
    {
        $station = CDISKitchenStation::where('bid', $bid)->first();
        return $station ? $station->toArray() : null;
    }

    public function getAllKitchenStations(): array
    {
        return CDISKitchenStation::where('status', 1)
            ->select('bid', 'name', 'code', 'order_type')
            ->get()
            ->toArray();
    }

    public function getTransactions(?string $kitchenStationBid, ?string $status = null, ?string $orderType = null): array
    {
        $query = KitchenDisplay::query()
            ->join('kitchen_display_detail', 'kitchen_display.bid', '=', 'kitchen_display_detail.head_bid')
            ->whereNull('kitchen_display.deleted_at')
            ->whereNull('kitchen_display_detail.deleted_at');

        if ($kitchenStationBid) {
            $query->where('kitchen_display_detail.kitchen_station_bid', $kitchenStationBid);
        }

        if ($status) {
            $query->where('kitchen_display_detail.status', $status);
        }

        if ($orderType) {
            $query->where('kitchen_display_detail.order_type_name', $orderType);
        }

        $query->whereNull('kitchen_display.ended_at');

        $heads = $query->select('kitchen_display.*')
            ->distinct()
            ->orderBy('kitchen_display.sent_at', 'asc')
            ->get();

        $results = [];
        foreach ($heads as $head) {
            $details = KitchenDisplayDetail::where('head_bid', $head->bid)
                ->whereNull('deleted_at')
                ->when($kitchenStationBid, function ($q) use ($kitchenStationBid) {
                    $q->where('kitchen_station_bid', $kitchenStationBid);
                })
                ->get();

            $results[] = [
                'bid' => $head->bid,
                'transaction_id' => $head->transaction_id,
                'terminal_number' => $head->terminal_number,
                'total_quantity' => $head->total_quantity,
                'completed_quantity' => $head->completed_quantity,
                'sent_at' => $head->sent_at,
                'items' => $details->map(function ($detail) {
                    return [
                        'bid' => $detail->bid,
                        'name' => $detail->name,
                        'quantity' => $detail->quantity,
                        'remaining_quantity' => $detail->remaining_quantity,
                        'prepared_quantity' => $detail->prepared_quantity,
                        'bumped_quantity' => $detail->bumped_quantity,
                        'status' => $detail->status,
                        'action_type' => $detail->action_type,
                        'order_type_name' => $detail->order_type_name,
                        'special_request' => $detail->special_request,
                        'addons' => $detail->addons,
                        'is_addon' => $detail->is_addon,
                        'kitchen_station_bid' => $detail->kitchen_station_bid,
                        'sent_at' => $detail->sent_at,
                        'prepared_at' => $detail->prepared_at,
                        'bumped_at' => $detail->bumped_at,
                    ];
                })->toArray(),
            ];
        }

        return $results;
    }

    public function getTransactionDetail(string $headBid): ?array
    {
        $head = KitchenDisplay::where('bid', $headBid)->first();
        if (!$head) {
            return null;
        }

        $details = KitchenDisplayDetail::where('head_bid', $headBid)
            ->whereNull('deleted_at')
            ->get();

        return [
            'bid' => $head->bid,
            'transaction_id' => $head->transaction_id,
            'terminal_number' => $head->terminal_number,
            'total_quantity' => $head->total_quantity,
            'completed_quantity' => $head->completed_quantity,
            'sent_at' => $head->sent_at,
            'items' => $details->map(function ($detail) {
                return [
                    'bid' => $detail->bid,
                    'name' => $detail->name,
                    'quantity' => $detail->quantity,
                    'remaining_quantity' => $detail->remaining_quantity,
                    'prepared_quantity' => $detail->prepared_quantity,
                    'bumped_quantity' => $detail->bumped_quantity,
                    'status' => $detail->status,
                    'action_type' => $detail->action_type,
                    'order_type_name' => $detail->order_type_name,
                    'special_request' => $detail->special_request,
                    'addons' => $detail->addons,
                    'is_addon' => $detail->is_addon,
                    'kitchen_station_bid' => $detail->kitchen_station_bid,
                ];
            })->toArray(),
        ];
    }

    public function prepareItem(array $payload): array
    {
        $detailBid = $payload['detail_bid'] ?? null;
        if (!$detailBid) {
            return ['success' => false, 'message' => 'detail_bid is required.', 'data' => null];
        }

        $detail = KitchenDisplayDetail::where('bid', $detailBid)->first();
        if (!$detail) {
            return ['success' => false, 'message' => 'Item not found.', 'data' => null];
        }

        $detail->update([
            'status' => 'PREPARING',
            'action_type' => 1,
            'prepared_at' => now(),
        ]);

        return ['success' => true, 'message' => 'Item prepared.', 'data' => $detail->toArray()];
    }

    public function bumpItem(array $payload): array
    {
        $detailBid = $payload['detail_bid'] ?? null;
        if (!$detailBid) {
            return ['success' => false, 'message' => 'detail_bid is required.', 'data' => null];
        }

        $detail = KitchenDisplayDetail::where('bid', $detailBid)->first();
        if (!$detail) {
            return ['success' => false, 'message' => 'Item not found.', 'data' => null];
        }

        $detail->update([
            'status' => 'DONE',
            'action_type' => 2,
            'bumped_at' => now(),
            'bumped_quantity' => $detail->quantity,
            'remaining_quantity' => 0,
        ]);

        $this->checkHeadCompletion($detail->head_bid);

        return ['success' => true, 'message' => 'Item bumped.', 'data' => $detail->toArray()];
    }

    public function undoItem(array $payload): array
    {
        $detailBid = $payload['detail_bid'] ?? null;
        if (!$detailBid) {
            return ['success' => false, 'message' => 'detail_bid is required.', 'data' => null];
        }

        $detail = KitchenDisplayDetail::where('bid', $detailBid)->first();
        if (!$detail) {
            return ['success' => false, 'message' => 'Item not found.', 'data' => null];
        }

        $detail->update([
            'status' => 'WAITING',
            'action_type' => 0,
            'prepared_at' => null,
            'bumped_at' => null,
            'bumped_quantity' => 0,
            'remaining_quantity' => $detail->quantity,
        ]);

        return ['success' => true, 'message' => 'Item undone.', 'data' => $detail->toArray()];
    }

    public function getSummary(?string $kitchenStationBid): array
    {
        $query = KitchenDisplayDetail::query()->whereNull('deleted_at');

        if ($kitchenStationBid) {
            $query->where('kitchen_station_bid', $kitchenStationBid);
        }

        $query->whereHas('head', function ($q) {
            $q->whereNull('ended_at')->whereNull('deleted_at');
        });

        $items = $query->select('name', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('name')
            ->orderBy('total_quantity', 'desc')
            ->get();

        return $items->map(function ($item) {
            return [
                'name' => $item->name,
                'total_quantity' => (int) $item->total_quantity,
            ];
        })->toArray();
    }

    private function checkHeadCompletion(string $headBid): void
    {
        $pendingCount = KitchenDisplayDetail::where('head_bid', $headBid)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'DONE')
            ->count();

        if ($pendingCount === 0) {
            KitchenDisplay::where('bid', $headBid)->update([
                'ended_at' => now(),
                'completed_at' => now(),
            ]);
        }
    }
}
