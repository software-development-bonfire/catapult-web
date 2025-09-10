<?php

namespace App\Http\Controllers\POS\v1;

use App\Entities\KitchenDisplayDetail;
use App\Enums\CDIS\TerminalTransactionType;
use App\Enums\UsageType;
use App\Events\KDSTransactionEvent;
use App\Events\MyPrivateEvent;
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
        \Illuminate\Support\Facades\Log::alert(json_encode($request->all()));
        $transactions = [];
        if (! empty($request->transaction)) {
            $transactions = app()->make(TerminalTransactionService::class)->store($request->transaction);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        $sendToKitchen = isset($transactions['transaction_type']) && ($transactions['transaction_type'] == TerminalTransactionType::SALES) ;
        $printToKitchen = isset($transactions['transaction_type']) && ($transactions['transaction_type'] == TerminalTransactionType::SALES || $transactions['transaction_type'] == TerminalTransactionType::REFUND) ;

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
                    $kitchenDisplayProducts[] = collect($product)->merge($kitchenDisplay);
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
        if ($sendToKitchen && count($transactionStickersProducts) > 0) {
            $printerHost = $this->getConfigStickerPrinter();
            $this->printStickerSeparately($printerHost, $transactionStickersProducts, $transactions);
        }

        /*
        $kitchenDisplayDetails = KitchenDisplayDetail::where('transaction_id', $transactions['kds_transaction']['transaction_id'])
        ->where('terminal_number', $transactions['kds_transaction']['terminal_number'])
        ->where('kitchen_station_index', 1)
        ->get();
        foreach($kitchenDisplayDetails as $kitchenDisplayDetail) {
            $kitchenDisplay = app()->make(KitchenItemSetupRepository::class)->getInitialKitchenStation($kitchenDisplayDetail['product_uom_packaging_bid']);
            $kitchenDisplayDetail['quantity'] = $kitchenDisplayDetail['remaining_quantity'];
            $kitchenDisplayDetail['product_bid'] = $kitchenDisplayDetail['product_uom_packaging_bid'];
            $kitchenDisplayProducts[] = collect($kitchenDisplayDetail)->merge($kitchenDisplay);
        }
            */
       // \Illuminate\Support\Facades\Log::alert(json_encode($kitchenDisplayProducts));
        if ($printToKitchen && count($kitchenDisplayProducts) == 0) {
            // Get configured Kitchen Display of each products
            $groupedDisplays = collect($kitchenDisplayProducts)->groupBy('device_uid');
            foreach ($groupedDisplays->toArray() as $device => $items) {
                if (! empty($device) && count($items) > 0) {
                    // Broadcast to assigned KDS
                    broadcast(new MyPrivateEvent($device, $transactions['kds_transaction'], $items, ''));
                }
            }

            // Grouped by order type name, then assigned items by order type susch DINE IN, TAKE OUT, DRIVE THRU, etc.
            $groupedReleasingDisplays = collect($kitchenDisplayProducts)->groupBy('order_type_name');
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

                    broadcast(new KDSTransactionEvent('', $transactions['kds_transaction'], $clonedItems, $orderType, 'add'));
                }
            }
        }

        //}

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
            $transactions = app()->make(TerminalTransactionRepository::class)->list($data->filters, $detailFilters);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        return $this->successfulResponse($transactions);
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
