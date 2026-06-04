<?php

namespace App\Http\Controllers\KDS\v1;

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
     *
     * Request body:
     * {
     *   "transaction": { "transaction_id": "...", "order_number": "...", "type": 1, ... },
     *   "items": [ { "name": "...", "quantity": 1, "is_addon": false, "usage_type": 1, "special_request": "" } ],
     *   "printer_host": "optional — IP or Windows printer name"
     * }
     */
    public function printOrder(Request $request): JsonResponse
    {
        $transaction = $request->get('transaction', []);
        $items       = $request->get('items', []);
        $printerHost = $request->get('printer_host') ?? $this->resolvePrinterHost($items);

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
     * Only items with moved_quantity > 0 and is_moved = true are printed.
     *
     * Request body:
     * {
     *   "transaction": { "transaction_id": "...", "order_number": "...", ... },
     *   "items": [
     *     { "name": "...", "quantity": 2, "moved_quantity": 1, "is_moved": true, "is_addon": false, "special_request": "" }
     *   ],
     *   "printer_host": "optional"
     * }
     */
    public function printBumpItem(Request $request): JsonResponse
    {
        $transaction = $request->get('transaction', []);
        $items       = $request->get('items', []);

        // Server-side guard: keep only genuinely bumped items
        $bumpedItems = array_values(array_filter($items, function ($item) {
            return floatval($item['moved_quantity'] ?? 0) > 0
                && filter_var($item['is_moved'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }));

        if (empty($bumpedItems)) {
            return $this->errorResponse([], 'No bumped items found (moved_quantity > 0 and is_moved = true required).');
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

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

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
}
