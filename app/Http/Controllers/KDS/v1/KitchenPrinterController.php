<?php

namespace App\Http\Controllers\KDS\v1;

use App\Entities\CDISTerminalTransaction;
use App\Entities\KitchenDisplay;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\KitchenPrinterRepository;
use App\Traits\KitchenPrinterTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KitchenPrinterController extends Controller
{
    use KitchenPrinterTrait;

    /**
     * Print a full kitchen order triggered from KDS.
     * Reconstructs items from the database to include ALL items under the transaction,
     * not just those visible on the current KDS station.
     *
     * Request body:
     * {
     *   "transaction": { "transaction_id": "...", "order_number": "...", "type": 1, ... },
     *   "items": [ ... ],
     *   "printer_host": "optional — IP or Windows printer name"
     * }
     */
    public function printOrder(Request $request): JsonResponse
    {
        $transaction = $request->get('transaction', []);
        $items       = $this->reconstructItems($transaction);
        $printerHost = $request->get('printer_host') ?? $this->resolvePrinterHost($items);

        if (empty($items)) {
            return $this->errorResponse([], 'No items found for this transaction.');
        }

        if (empty($printerHost)) {
            return $this->errorResponse([], 'No printer host configured or found for the given items.');
        }

        try {
            $this->printKitchenOrder($printerHost, $items, $transaction);
            return $this->successfulResponse([], 'Order printed successfully.');
        } catch (\Exception $e) {
            Log::error('KitchenPrinterController::printOrder — ' . $e->getMessage());
            return $this->errorResponse([], $e->getMessage());
        }
    }

    /**
     * Print bumped items from KDS.
     * Reconstructs items from the database and uses bumped_quantity from KDS payload
     * to determine which items and quantities to print.
     *
     * Request body:
     * {
     *   "transaction": { "transaction_id": "...", "order_number": "...", ... },
     *   "items": [
     *     { "product_bid": "...", "name": "...", "quantity": 2, "bumped_quantity": 1, "is_addon": false, "special_request": "" }
     *   ],
     *   "printer_host": "optional"
     * }
     */
    public function printBumpItem(Request $request): JsonResponse
    {
        $transaction = $request->get('transaction', []);
        $items       = $request->get('items', []);

        // Use bumped_quantity from KDS payload to build consolidated bumped items
        // If items is empty, fetches from KitchenDisplay/KitchenDisplayDetail
        $bumpedItems = $this->consolidateBumpedItems($items, $transaction);

        if (empty($bumpedItems)) {
            return $this->errorResponse([], 'No bumped items found (bumped_quantity > 0 required).');
        }

        $printerHost = $request->get('printer_host') ?? $this->resolvePrinterHost($bumpedItems);

        if (empty($printerHost)) {
            return $this->errorResponse([], 'No printer host configured or found for the given items.');
        }

        try {
            $this->printBumpItems($printerHost, $bumpedItems, $transaction);
            return $this->successfulResponse([], 'Bumped items printed successfully.');
        } catch (\Exception $e) {
            Log::error('KitchenPrinterController::printBumpItem — ' . $e->getMessage());
            return $this->errorResponse([], $e->getMessage());
        }
    }

    /**
     * Print only the table number in large centered text.
     *
     * Request body:
     * {
     *   "transaction": { "table_number": "Table 5", "order_number": "...", "transaction_id": "..." },
     *   "printer_host": "optional"
     * }
     */
    public function printTableNumber(Request $request): JsonResponse
    {
        $transaction = $request->get('transaction', []);
        $printerHost = $request->get('printer_host') ?? $this->resolveDefaultPrinterHost();

        if (empty($printerHost)) {
            return $this->errorResponse([], 'No printer host configured. Pass printer_host in the request or set PRINTER_KITCHEN in .env.');
        }

        try {
            $this->printTableNo($printerHost, $transaction);
            return $this->successfulResponse([], 'Table number printed successfully.');
        } catch (\Exception $e) {
            Log::error('KitchenPrinterController::printTableNumber — ' . $e->getMessage());
            return $this->errorResponse([], $e->getMessage());
        }
    }

    /**
     * Resolve the printer host from the first item that has a product_bid or
     * product_uom_packaging_bid mapped to a kitchen printer.
     */
    private function resolvePrinterHost(array $items): ?string
    {
        foreach ($items as $item) {
            $productBid = $item['product_bid'] ?? $item['product_uom_packaging_bid'] ?? null;
            if (empty($productBid)) {
                continue;
            }

            $printer = app()->make(KitchenPrinterRepository::class)->getProductKitchenPrinter($productBid);
            if ($printer) {
                if (!empty($printer->local_printer)) {
                    return $printer->local_printer;
                }
                if (!empty($printer->printer_host)) {
                    return $printer->printer_host;
                }
            }
        }

        return $this->resolveDefaultPrinterHost();
    }

    /**
     * Fall back to the PRINTER_KITCHEN env value when no product mapping is found.
     */
    private function resolveDefaultPrinterHost(): ?string
    {
        return config('system.printers.kitchen') ?: null;
    }

    /**
     * Reconstruct all items from the database for a given transaction.
     * Uses KitchenDisplay/KitchenDisplayDetail to get all items and sums
     * remaining_quantity by product to get the original quantity per item.
     */
    private function reconstructItems(array $transaction): array
    {
        $transactionId = $transaction['transaction_id'] ?? null;
        $terminalBid = $transaction['terminal_bid'] ?? null;

        if (!$transactionId || !$terminalBid) {
            return [];
        }

        $kitchenDisplay = KitchenDisplay::with('details')
            ->where('transaction_id', $transactionId)
            ->where('terminal_bid', $terminalBid)
            ->get();

        if ($kitchenDisplay->isEmpty()) {
            return [];
        }

        $consolidated = [];

        foreach ($kitchenDisplay as $display) {
            foreach ($display->details as $detail) {
                $key = $detail->product_uom_packaging_bid ?? $detail->transaction_product_bid;

                if (isset($consolidated[$key])) {
                    $consolidated[$key]['quantity'] += floatval($detail->quantity ?? $detail->remaining_quantity);
                } else {
                    $consolidated[$key] = [
                        'product_bid' => $detail->product_uom_packaging_bid,
                        'name' => $detail->name,
                        'quantity' => floatval($detail->quantity ?? $detail->remaining_quantity),
                        'usage_type' => $detail->usage_type,
                        'special_request' => $detail->special_request ?? '',
                        'is_addon' => (bool) $detail->is_addon,
                    ];
                }
            }
        }

        return array_values($consolidated);
    }

    /**
     * Consolidate bumped items by product_bid, summing released_quantity.
     * If items from request are empty, fetches from KitchenDisplay/KitchenDisplayDetail
     * where released_quantity > 0.
     * Returns items in the format expected by printBumpItems().
     */
    private function consolidateBumpedItems(array $items, array $transaction = []): array
    {
        // If no items from request, reconstruct from KitchenDisplay/KitchenDisplayDetail
        if (empty($items)) {
            $transactionId = $transaction['transaction_id'] ?? null;
            $terminalBid = $transaction['terminal_bid'] ?? null;

            if (!$transactionId || !$terminalBid) {
                return [];
            }

            $kitchenDisplays = KitchenDisplay::with(['details' => function ($query) {
                $query->where('released_quantity', '>', 0);
            }])
                ->where('transaction_id', $transactionId)
                ->where('terminal_bid', $terminalBid)
                ->get();

            if ($kitchenDisplays->isEmpty()) {
                return [];
            }

            $consolidated = [];

            foreach ($kitchenDisplays as $display) {
                foreach ($display->details as $detail) {
                    $key = $detail->product_uom_packaging_bid ?? $detail->transaction_product_bid;

                    if (isset($consolidated[$key])) {
                        $consolidated[$key]['moved_quantity'] += floatval($detail->released_quantity);
                    } else {
                        $consolidated[$key] = [
                            'product_bid' => $detail->product_uom_packaging_bid,
                            'name' => $detail->name,
                            'quantity' => floatval($detail->quantity ?? $detail->remaining_quantity),
                            'moved_quantity' => floatval($detail->released_quantity),
                            'is_moved' => true,
                            'usage_type' => $detail->usage_type,
                            'special_request' => $detail->special_request ?? '',
                            'is_addon' => (bool) $detail->is_addon,
                        ];
                    }
                }
            }

            return array_values($consolidated);
        }

        // Consolidate from request payload
        $consolidated = [];

        foreach ($items as $item) {
            $releasedQty = floatval($item['released_quantity'] ?? $item['bumped_quantity'] ?? $item['moved_quantity'] ?? 0);
            if ($releasedQty <= 0) {
                continue;
            }

            $key = $item['product_bid'] ?? $item['name'] ?? uniqid();

            if (isset($consolidated[$key])) {
                $consolidated[$key]['moved_quantity'] += $releasedQty;
            } else {
                $consolidated[$key] = [
                    'product_bid' => $item['product_bid'] ?? null,
                    'name' => $item['name'] ?? '',
                    'quantity' => floatval($item['quantity'] ?? 0),
                    'moved_quantity' => $releasedQty,
                    'is_moved' => true,
                    'usage_type' => $item['usage_type'] ?? null,
                    'special_request' => $item['special_request'] ?? '',
                    'is_addon' => $item['is_addon'] ?? false,
                ];
            }
        }

        return array_values($consolidated);
    }
}
