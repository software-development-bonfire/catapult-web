<?php

namespace App\Http\Controllers\POS\v1;

use App\Entities\CDISTerminal;
use App\Entities\KitchenDisplayDetail;
use App\Entities\StationOTSTerminalTransaction;
use App\Enums\CDIS\TerminalTransactionType;
use App\Enums\KDS\OrderType;
use App\Enums\OTS\SourceTransactionType;
use App\Enums\POS\DeviceMode;
use App\Enums\UsageType;
use App\Events\KDS\KDSTransactionEvent;
use App\Events\KDS\KDSDeviceEvent;
use App\Events\OTS\OTSSettledEvent;
use App\Events\PrintEvent;
use App\Http\Controllers\POS\POSBaseController;
use App\Jobs\KDS\PrintToKitchenPrinter as KDSPrintToKitchenPrinter;
use App\Repositories\Contracts\DeviceSettingsRepository;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Repositories\Contracts\KitchenPrinterRepository;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
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
        \Illuminate\Support\Facades\Log::alert('TRANSACTION: ' . json_encode($request->all()));
        $transactions = [];
        if (! empty($request->transaction)) {
            // let's check first the transaction terminal number and branch code
            foreach ($request->transaction as $datum) {
                $datum = (object) $datum;
                $branchCode = config('configuration.branch_code');

                $terminal = CDISTerminal::find($datum->terminal_bid);
                if (! $terminal) {
                    // Return error if terminal not found with human readable message
                    return $this->errorResponse([], 'Invalid terminal (' . $datum->terminal_bid . ') or not found on branch ' . $branchCode);
                }
            }
            $transactions = app()->make(TerminalTransactionService::class)->store($request->transaction);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }
        \Illuminate\Support\Facades\Log::alert('transactions: ' . json_encode($transactions));
        $isNotFastFood = isset($transactions['device_mode']) && ($transactions['device_mode'] !== DeviceMode::FAST_FOOD);
        if ($isNotFastFood) {
            if (isset($transactions['is_settled']) && $transactions['is_settled'] === true) {
                // If fine-dine transaction from POS and it settled, broadcast to all Station OTS
                broadcast(new OTSSettledEvent($transactions['tabled_id'] ?? 'Add `table_id` on the request body', $transactions['device_code'] ?? 'Add `device_code` on the request body', $transactions['kds_transaction']));

                Log::alert('Fine-dine transaction settled with bid: ' . ($transactions['bid'] ?? 'N/A') . ' and transaction_id: ' . ($transactions['transaction_id'] ?? 'N/A') . '. Broadcasting settlement to Station OTS.');
                // Delete the fine-dine transaction and its details from station_ots_terminal_transactions
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
                // No need to send to KDS since the transaction already settled, and usually for fine-dine transaction, 
                // the order will be sent to KDS immediately once the order is created on POS, so we can assume that the order already exist on KDS, 
                // and we just need to update the status on KDS once the transaction is settled, which is handled by OTSSettledEvent
                return $this->successfulResponse(
                    $transactions,
                    Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
                );
            } else {
                // @TODO: Add here broadcasting events for KDS, this event should be send to KDS when fine-dine transaction is created/updated on POS, so KDS can display the transaction immediately without waiting for the settlement, since fine-dine transaction usually will be settled after the meal, and we want to make sure that the order will be displayed on KDS as soon as possible once the order is created on POS
                // Broadcast to all Station OTS that fine-dine transaction is created/updated
                //broadcast(new KDSDeviceEvent('fine-dine', $transactions['kds_transaction'], [], 'fine-dine-transaction'));
                Log::alert('Fine-dine transaction created/updated with bid: ' . ($transactions['bid'] ?? 'N/A') . ' and transaction_id: ' . ($transactions['transaction_id'] ?? 'N/A') . '. Broadcasting to KDS is still to be implemented.');
            }
        }

        $printToSticker = isset($transactions['transaction_type']) && ($transactions['transaction_type'] == TerminalTransactionType::SALES);
        $printToKitchen = isset($transactions['transaction_type']) && ($transactions['transaction_type'] == TerminalTransactionType::SALES || $transactions['transaction_type'] == TerminalTransactionType::REFUND);

        // Print only SALES transaction type on Sticker/Kitchen Printer
        //if ((isset($transactions->type) && $transactions->type == TerminalTransactionType::SALES) && empty($transactions->is_reprint)) {
        $transactionProducts = [];
        $transactionStickersProducts = [];
        if (isset($transactions['official_receipt']['products'])) {
            foreach ($transactions['official_receipt']['products'] as $product) {
                $transactionProducts[] = $product;

                // Validate product if enabled for printing sticker
                $productPackaging = app()->make(KitchenPrinterRepository::class)->getProductIsPrintSticker($product['product_bid']);
                if ($productPackaging && (isset($productPackaging->is_print_sticker) && $productPackaging->is_print_sticker == 1)) {
                    $transactionStickersProducts[] = $product;
                }
            }
        }
        $flattenProducts = [];
        $kitchenDisplayProducts = [];
        if (isset($transactions['official_receipt']['flatten_products'])) {
            foreach ($transactions['official_receipt']['flatten_products'] as $product) {
                $kitchenPrinter = app()->make(KitchenPrinterRepository::class)->getProductKitchenPrinter($product['product_bid']);
                $flattenProducts[] = collect($product)->merge($kitchenPrinter);

                if ($product['has_addon'] == false) {
                    $kitchenDisplay = app()->make(KitchenItemSetupRepository::class)->getInitialKitchenStation($product['product_bid']);
                    if ($kitchenDisplay) {
                        $kitchenDisplayProducts[] = collect($product)->merge($kitchenDisplay->toArray());
                    }
                }
            }
        }

        if ($printToKitchen && count($flattenProducts) > 0) {
            // Get configured local printer of each products
            $groupedPrinters = collect($flattenProducts)->groupBy('local_printer');
            foreach ($groupedPrinters->toArray() as $printerHost => $items) {
                // Reconstruct transaction product list to be printed on Kitchen Printer

                if (! empty($printerHost) && count($items) > 0) {
                    $this->printKitchen($printerHost, $items, $transactions); // Call print directly
                    // Uncomment below code if you want to QUEUE kitchen printing, instead of calling $this->printKitchen
                    //KDSPrintToKitchenPrinter::dispatch($printerHost, $items, $transactions); // Add kitchen printing on the queue
                }
            }
        }

        // Call sticker printing when printable for stickers are present
        if ($printToSticker && count($transactionStickersProducts) > 0) {
            $printerHost = $this->getConfigStickerPrinter();
            try {
                $this->printStickerSeparately($printerHost, $transactionStickersProducts, $transactions);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::alert('VALIDATION: ' . json_encode([
                    'printToKitchen' => $printToKitchen,
                    'count' => count($kitchenDisplayProducts),
                    'error' => $e->getMessage()
                ]));
            }
        }

        \Illuminate\Support\Facades\Log::alert('VALIDATION: ' . json_encode([
            'printToKitchen' => $printToKitchen,
            'count' => count($kitchenDisplayProducts)
        ]));

        if ($printToKitchen && count($kitchenDisplayProducts) > 0) {
            // Get configured Kitchen Display of each products
            $groupedDisplays = collect($kitchenDisplayProducts)->groupBy('device_uid');
            foreach ($groupedDisplays->toArray() as $device => $items) {
                if (! empty($device) && count($items) > 0) {
                    // Broadcast to assigned KDS
                    \Illuminate\Support\Facades\Log::info('Broadcasting to device: ' . $device . ' with ' . count($items) . ' items');
                    broadcast(new KDSDeviceEvent($device, $transactions['kds_transaction'], $items, ''));
                }
            }

            // Grouped by order type id, then assigned items by order type such DINE IN, TAKE OUT, DRIVE THRU, etc.
            $groupedReleasingDisplays = collect($kitchenDisplayProducts)->groupBy('order_type_id');
            foreach ($groupedReleasingDisplays->toArray() as $orderType => $items) {
                if (! empty($orderType) && count($items) > 0) {
                    // Broadcast to assigned KDS for Releasing
                    // Clone and modify the items
                    $clonedItems = collect($items)->map(function ($item) {
                        if (isset($item['quantity'])) {
                            // $item['quantity'] = 0; // Set quantity to 0 to make sure on the first display on releasing
                        }
                        if (isset($item['remaining_quantity'])) {
                            // $item['remaining_quantity'] = 0; // Set remaining_quantity to 0
                        }
                        return $item;
                    })->toArray(); // Convert back to array if needed

                    $orderTypeName = OrderType::getDescription($orderType);
                    \Illuminate\Support\Facades\Log::alert('BROADCAST: ' . $orderType . ': ' . $orderTypeName);
                    \Illuminate\Support\Facades\Log::alert(json_encode([
                        'device' => $orderType,
                        'transaction' =>  $transactions['kds_transaction'],
                        'items' => $clonedItems,
                        'releasing' => $orderTypeName,
                        'type' => 'add',
                    ]));
                    broadcast(new KDSTransactionEvent($orderType, $transactions['kds_transaction'], $clonedItems, $orderTypeName, 'add'));
                }
            }
        }

        return $this->successfulResponse(
            $transactions,
            Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
        );
    }

    public function update(Request $request)
    {
        $data = (object) stringToJson($request->all());
        if (! empty($data->data)) {
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
        $transactions = null;
        $data = (object) stringToJson($request->all());
        if (! empty($data->filters)) {
            // Filters only non-modifiers, non-addons products in details
            // Let the relationship query handle the rest
            $detailFilters = ['usage_type' => UsageType::PRODUCT];
            
            $filters = (object) $data->filters;
            $paymentFilters = !empty($filters->payments) ? (array) $filters->payments : null;
            $transactions = app()->make(TerminalTransactionRepository::class)->list($data->filters, $detailFilters, $paymentFilters);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        return $this->successfulResponse($transactions);
    }

    public function search2(Request $request)
    {
        $data = (object) stringToJson($request->all());

        if (empty($data->filters)) {
            return $this->errorResponse([], 'Missing request parameters');
        }

        // Get only main products (not addons/modifiers) in the details
        $detailFilters = [
            'usage_type' => UsageType::PRODUCT
        ];

        // Add any additional detail filters from request
        if (!empty($data->detail_filters)) {
            $detailFilters = array_merge($detailFilters, (array) $data->detail_filters);
        }

        // Handle payment filters
        $paymentFilters = !empty($data->payment_filters) ? (array) $data->payment_filters : null;

        // Option to include or exclude children
        $includeChildren = isset($data->include_children) ? $data->include_children : true;

        $transactions = app()->make(TerminalTransactionRepository::class)
            ->list($data->filters, $detailFilters, $paymentFilters, $includeChildren);

        // Optionally transform the data to organize addons/modifiers under their parents
        if ($includeChildren && $request->get('organize_children', false)) {
            $transactions = $this->organizeTransactionChildren($transactions);
        }

        return $this->successfulResponse($transactions);
    }

    /**
     * Organize transaction details to nest addons/modifiers under their parents
     */
    protected function organizeTransactionChildren($transactions)
    {
        return $transactions->map(function ($transaction) {
            if ($transaction->details->isEmpty()) {
                return $transaction;
            }

            $organizedDetails = collect();
            $childrenByParent = [];

            // First pass: separate parents and children
            foreach ($transaction->details as $detail) {
                if (in_array($detail->usage_type, [UsageType::BUNDLE, UsageType::ADDON])) {
                    // This is a child
                    if (!isset($childrenByParent[$detail->parent_bid])) {
                        $childrenByParent[$detail->parent_bid] = collect();
                    }
                    $childrenByParent[$detail->parent_bid]->push($detail);
                } else {
                    // This is a parent or standalone product
                    $detail->children = collect();
                    $organizedDetails->push($detail);
                }
            }

            // Second pass: attach children to their parents
            foreach ($organizedDetails as $parent) {
                if (isset($childrenByParent[$parent->product_bid])) {
                    $parent->children = $childrenByParent[$parent->product_bid];
                }
            }

            $transaction->organized_details = $organizedDetails;

            // Keep the original details as well for backward compatibility
            return $transaction;
        });
    }

    public function printReceipt(Request $request)
    {
        $data = stringToJson($request->all());
        // If done processing on Sirius POS then send back to Kiosk the constructed RECEIPT
        // <source> should be the device identifier of the KIOSK
        broadcast(new PrintEvent($data->source, $data->content));
        return $this->successfulResponse($data);
    }
}
