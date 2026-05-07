<?php

namespace App\Http\Controllers\POS\v1;

use App\Entities\CDISTerminal;
use App\Entities\StationOTSTerminalTransaction;
use App\Enums\CDIS\TerminalTransactionType;
use App\Enums\KDS\OrderType;
use App\Enums\OTS\SourceTransactionType;
use App\Enums\POS\DeviceMode;
use App\Enums\UsageType;
use App\Events\KDS\KDSFastFoodTransactionEvent;
use App\Events\KDS\KDSFineDineTransactionEvent;
use App\Events\OTS\OTSSettledEvent;
use App\Events\PrintEvent;
use App\Http\Controllers\POS\POSBaseController;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Repositories\Contracts\KitchenPrinterRepository;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Services\KDS\KDSTransactionService;
use App\Services\POS\TerminalTransactionService;
use App\Traits\KitchenDisplayTrait;
use App\Traits\KitchenPrinterTrait;
use App\Traits\StickerPrinterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class TerminalTransactionController extends POSBaseController
{
    use KitchenDisplayTrait;
    use KitchenPrinterTrait;
    use StickerPrinterTrait;

    public function store(Request $request)
    {
        Log::alert('TRANSACTION: ' . json_encode($request->all()));

        if (empty($request->transaction)) {
            return $this->errorResponse([], 'Missing request parameters');
        }

        // Validate terminals exist
        foreach ($request->transaction as $datum) {
            $datum = (object) $datum;
            $terminal = CDISTerminal::find($datum->terminal_bid);
            if (!$terminal) {
                $branchCode = config('configuration.branch_code');
                return $this->errorResponse([], 'Invalid terminal (' . $datum->terminal_bid . ') or not found on branch ' . $branchCode);
            }
        }

        // Step 1: Store transaction data to DB
        $transactions = app()->make(TerminalTransactionService::class)->store($request->transaction);

        // Step 2: Construct KDS data and persist to KDS tables
        if (!empty($transactions)) {
            $kdsData = app()->make(KDSTransactionService::class)->store($transactions);

            // Merge KDS data into transactions for broadcasting
            $transactions['kds_transaction'] = $kdsData['kds_transaction'] ?? null;
            $transactions['flatten_products'] = $kdsData['flatten_products'] ?? [];
        }

        // Step 3: Handle fine-dine (non-fast-food) flow
        if ($this->isFineDineTransaction($transactions)) {
            return $this->handleFineDineTransaction($transactions);
        }

        // Step 4: Handle kitchen printer, sticker printer, and KDS broadcasting
        $this->handleKitchenPrinting($transactions);
        $this->handleStickerPrinting($transactions);
        $this->handleKDSBroadcasting($transactions, false);

        return $this->successfulResponse(
            $transactions,
            Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
        );
    }

    /**
     * Check if this is a fine-dine (non-fast-food) transaction
     */
    private function isFineDineTransaction($transactions): bool
    {
        return isset($transactions['device_mode']) && ($transactions['device_mode'] !== DeviceMode::FAST_FOOD);
    }

    /**
     * Handle fine-dine transaction broadcasting and settlement
     */
    private function handleFineDineTransaction($transactions)
    {
        if (isset($transactions['is_settled']) && $transactions['is_settled'] === true) {
            broadcast(new OTSSettledEvent(
                $transactions['tabled_id'] ?? 'Add `table_id` on the request body',
                $transactions['device_code'] ?? 'Add `device_code` on the request body',
                $transactions['kds_transaction']
            ));

            Log::alert('Fine-dine transaction settled with bid: ' . ($transactions['bid'] ?? 'N/A') . ' and transaction_id: ' . ($transactions['transaction_id'] ?? 'N/A'));

            // Clean up OTS transaction
            $this->cleanupOTSTransaction($transactions);

            return $this->successfulResponse(
                $transactions,
                Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
            );
        }

        // Non-settled fine-dine: still broadcast to KDS for kitchen preparation
        Log::alert('Fine-dine transaction created/updated with bid: ' . ($transactions['bid'] ?? 'N/A') . ' and transaction_id: ' . ($transactions['transaction_id'] ?? 'N/A') . '. Broadcasting to KDS.');

        // Process printing and KDS for non-settled fine-dine
        $this->handleKitchenPrinting($transactions);
        $this->handleStickerPrinting($transactions);
        $this->handleKDSBroadcasting($transactions, true);

        return $this->successfulResponse(
            $transactions,
            Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
        );
    }

    /**
     * Remove OTS transaction records after settlement
     */
    private function cleanupOTSTransaction($transactions)
    {
        $otsTransaction = null;

        if (!empty($transactions['bid'])) {
            $otsTransaction = StationOTSTerminalTransaction::where('bid', $transactions['bid'])->first();
        }

        if (!$otsTransaction && !empty($transactions['terminal_bid']) && !empty($transactions['transaction_id'])) {
            $otsTransaction = StationOTSTerminalTransaction::where('terminal_bid', $transactions['terminal_bid'])
                ->where('transaction_id', $transactions['transaction_id'])
                ->first();
        }

        if ($otsTransaction) {
            $otsTransaction->details()->delete();
            $otsTransaction->delete();
        }
    }

    /**
     * Handle kitchen printer output for transaction products
     */
    private function handleKitchenPrinting($transactions)
    {
        $printToKitchen = isset($transactions['transaction_type'])
            && (
                ($transactions['transaction_type'] == TerminalTransactionType::SALES ||
                    $transactions['transaction_type'] == TerminalTransactionType::REFUND) ||
                $transactions['transaction_type'] == SourceTransactionType::FINEDINE // For fine-dine, we want to send to KDS even if it's not marked as SALES/REFUND for preparation purposes
            );
        if (!$printToKitchen || empty($transactions['flatten_products'])) {
            return;
        }

        // Enrich flatten products with kitchen printer config
        $flattenProducts = [];
        foreach ($transactions['flatten_products'] as $product) {
            $kitchenPrinter = app()->make(KitchenPrinterRepository::class)->getProductKitchenPrinter($product['product_bid']);
            $flattenProducts[] = collect($product)->merge($kitchenPrinter);
        }

        if (count($flattenProducts) <= 0) {
            return;
        }

        // Group by local printer and print
        $groupedPrinters = collect($flattenProducts)->groupBy('local_printer');
        foreach ($groupedPrinters->toArray() as $printerHost => $items) {
            if (!empty($printerHost) && count($items) > 0) {
                $this->printKitchen($printerHost, $items, $transactions);
            }
        }
    }

    /**
     * Handle sticker printer output for transaction products
     */
    private function handleStickerPrinting($transactions)
    {
        $printToSticker = isset($transactions['transaction_type'])
            && ($transactions['transaction_type'] == TerminalTransactionType::SALES || $transactions['transaction_type'] == SourceTransactionType::FINEDINE);

        if (!$printToSticker || !isset($transactions['official_receipt']['products'])) {
            return;
        }
        
        $transactionStickersProducts = [];
        foreach ($transactions['official_receipt']['products'] as $product) {
            $productPackaging = app()->make(KitchenPrinterRepository::class)->getProductIsPrintSticker($product['product_bid']);
            if ($productPackaging && (isset($productPackaging->is_print_sticker) && $productPackaging->is_print_sticker == 1)) {
                $transactionStickersProducts[] = $product;
            }
        }

        if (count($transactionStickersProducts) <= 0) {
            Log::alert('No need to print stickers for transaction_id: ' . ($transactions['transaction_id'] ?? 'N/A') . ' with transaction_type: ' . ($transactions['transaction_type'] ?? 'N/A') . '. transactionStickersProducts count: ' . count($transactionStickersProducts));
            return;
        }

        $printerHost = $this->getConfigStickerPrinter();
        try {
            $this->printStickerSeparately($printerHost, $transactionStickersProducts, $transactions);
        } catch (\Exception $e) {
            Log::alert('STICKER_PRINT_ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Handle KDS broadcasting to kitchen displays and releasing stations
     */
    private function handleKDSBroadcasting($transactions, $isFineDine = false)
    {
        // SALES/REFUND is initially from FASTFOOD flow, but for FINE DINE developer put it on the transaction type for some reason.
        $sendToKitchenDisplay = isset($transactions['transaction_type'])
            && (
                ($transactions['transaction_type'] == TerminalTransactionType::SALES ||
                    $transactions['transaction_type'] == TerminalTransactionType::REFUND) ||
                $transactions['transaction_type'] == SourceTransactionType::FINEDINE // For fine-dine, we want to send to KDS even if it's not marked as SALES/REFUND for preparation purposes
            );

        // Only proceed if we need to send to kitchen display and there are products to send
        if (!$sendToKitchenDisplay || empty($transactions['flatten_products'])) {
            Log::alert('No need to broadcast to KDS for transaction_id: ' . ($transactions['transaction_id'] ?? 'N/A') . ' with transaction_type: ' . ($transactions['transaction_type'] ?? 'N/A') . '. sendToKitchenDisplay: ' . ($sendToKitchenDisplay ? 'true' : 'false') . ' and flatten_products count: ' . count($transactions['flatten_products']));
            return;
        }

        // Build kitchen display products (only non-addon products with kitchen station config)
        $kitchenDisplayProducts = [];
        foreach ($transactions['flatten_products'] as $product) {
            if ($product['has_addon'] == false) {
                $kitchenDisplay = app()->make(KitchenItemSetupRepository::class)->getInitialKitchenStation($product['product_bid']);
                if ($kitchenDisplay) {
                    $kitchenDisplayProducts[] = collect($product)->merge($kitchenDisplay->toArray());
                }
            }
        }

        if (count($kitchenDisplayProducts) <= 0) {
            return;
        }

        // Broadcast to assigned KDS devices
        $this->broadcastToKDSDevices($kitchenDisplayProducts, $transactions);

        // Broadcast to releasing stations by order type
        if (!$isFineDine) {
            // If FASTFOOD sento releasing stations immediately for order type flow
            // If FINE-DINE we will only send to KDS station for preparation, and the releasing station flow will be handled when the order is marked as done by station device.
            $this->broadcastToReleasingStations($kitchenDisplayProducts, $transactions);
        }
    }

    /**
     * Broadcast transaction items to specific KDS device displays
     */
    private function broadcastToKDSDevices($kitchenDisplayProducts, $transactions)
    {
        $isFineDine = $this->isFineDineTransaction($transactions);
        $groupedDisplays = collect($kitchenDisplayProducts)->groupBy('device_uid');

        foreach ($groupedDisplays->toArray() as $device => $items) {
            if (!empty($device) && count($items) > 0) {
                Log::info('Broadcasting to device: ' . $device . ' with mode ' . ($isFineDine ? 'FineDine' : 'FastFood') . ' and ' . count($items) . ' items');

                if ($isFineDine) {
                    broadcast(new KDSFineDineTransactionEvent($device, $transactions['kds_transaction'], $items));
                } else {
                    broadcast(new KDSFastFoodTransactionEvent($device, $transactions['kds_transaction'], $items));
                }
            }
        }
    }

    /**
     * Broadcast transaction items to releasing stations grouped by order type
     */
    private function broadcastToReleasingStations($kitchenDisplayProducts, $transactions)
    {
        $groupedReleasingDisplays = collect($kitchenDisplayProducts)->groupBy('order_type_id');

        foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
            if (empty($orderType) || count($items) <= 0) {
                continue;
            }

            $orderTypeName = OrderType::getDescription($orderType);
            Log::alert('BROADCAST: ' . $orderType . ': ' . $orderTypeName);

            // Broadcast releasing transaction to each device that has items
            $deviceUids = collect($items)->pluck('device_uid')->unique()->filter();
            foreach ($deviceUids as $deviceUid) {
                $deviceItems = collect($items)->where('device_uid', $deviceUid)->values()->toArray();
                broadcast(new KDSFastFoodTransactionEvent($deviceUid, $transactions['kds_transaction'], $deviceItems, true));
            }
        }
    }

    public function update(Request $request)
    {
        $data = (object) stringToJson($request->all());
        if (!empty($data->data)) {
            $result = app()->make(TerminalTransactionService::class)->updateStatus($data->data);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        return $this->successfulResponse($result);
    }

    public function list(Request $request)
    {
        $filters = stringToJson($request->get('filters'));
        $transactions = app()->make(TerminalTransactionRepository::class)->list($filters);

        return $this->successfulResponse($transactions);
    }

    public function search(Request $request)
    {
        $data = (object) stringToJson($request->all());
        if (empty($data->filters)) {
            return $this->errorResponse([], 'Missing request parameters');
        }

        $detailFilters = ['usage_type' => UsageType::PRODUCT];

        $filters = (object) $data->filters;
        $paymentFilters = !empty($filters->payments) ? (array) $filters->payments : null;
        $transactions = app()->make(TerminalTransactionRepository::class)->list($data->filters, $detailFilters, $paymentFilters);

        return $this->successfulResponse($transactions);
    }

    public function search2(Request $request)
    {
        $data = (object) stringToJson($request->all());

        if (empty($data->filters)) {
            return $this->errorResponse([], 'Missing request parameters');
        }

        $detailFilters = ['usage_type' => UsageType::PRODUCT];

        if (!empty($data->detail_filters)) {
            $detailFilters = array_merge($detailFilters, (array) $data->detail_filters);
        }

        $paymentFilters = !empty($data->payment_filters) ? (array) $data->payment_filters : null;
        $includeChildren = isset($data->include_children) ? $data->include_children : true;

        $transactions = app()->make(TerminalTransactionRepository::class)
            ->list($data->filters, $detailFilters, $paymentFilters, $includeChildren);

        if ($includeChildren && $request->get('organize_children', false)) {
            $transactions = $this->organizeTransactionChildren($transactions);
        }

        return $this->successfulResponse($transactions);
    }

    protected function organizeTransactionChildren($transactions)
    {
        return $transactions->map(function ($transaction) {
            if ($transaction->details->isEmpty()) {
                return $transaction;
            }

            $organizedDetails = collect();
            $childrenByParent = [];

            foreach ($transaction->details as $detail) {
                if (in_array($detail->usage_type, [UsageType::BUNDLE, UsageType::ADDON])) {
                    if (!isset($childrenByParent[$detail->parent_bid])) {
                        $childrenByParent[$detail->parent_bid] = collect();
                    }
                    $childrenByParent[$detail->parent_bid]->push($detail);
                } else {
                    $detail->children = collect();
                    $organizedDetails->push($detail);
                }
            }

            foreach ($organizedDetails as $parent) {
                if (isset($childrenByParent[$parent->product_bid])) {
                    $parent->children = $childrenByParent[$parent->product_bid];
                }
            }

            $transaction->organized_details = $organizedDetails;
            return $transaction;
        });
    }

    public function printReceipt(Request $request)
    {
        $data = stringToJson($request->all());
        broadcast(new PrintEvent($data->source, $data->content));
        return $this->successfulResponse($data);
    }
}
