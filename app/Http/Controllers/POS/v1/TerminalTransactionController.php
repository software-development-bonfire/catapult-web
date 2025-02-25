<?php

namespace App\Http\Controllers\POS\v1;

use App\Http\Controllers\POS\POSBaseController;
use App\Jobs\KDS\PrintToKitchenPrinter as KDSPrintToKitchenPrinter;
use App\Repositories\Contracts\KitchenPrinterRepository;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Services\POS\TerminalTransactionService;
use App\Traits\KitchenDisplayTrait;
use App\Traits\KitchenPrinterTrait;
use App\Traits\StickerPrinterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class TerminalTransactionController extends POSBaseController
{
    use KitchenDisplayTrait;
    use KitchenPrinterTrait;
    use StickerPrinterTrait;

    public function store(Request $request)
    {
        $transactions = [];
        if (! empty($request->transaction)) {
            $transactions = app()->make(TerminalTransactionService::class)->store($request->transaction);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        unset($request->access_token);

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
        if (isset($transactions['official_receipt']['flatten_products'])) {
            foreach ($transactions['official_receipt']['flatten_products'] as $product) {
                $flattenProducts[] = $product;
            }
        }

        // Get configured local printer of each products
        $kitchenTransactions = app()->make(KitchenPrinterRepository::class)->getMenuPrinters($transactionProducts);
        $groupedPrinters = collect($kitchenTransactions)->groupBy('local_printer');
        foreach ($groupedPrinters->toArray() as $printerHost => $items) {
            // Reconstruct transaction product list to be printed on Kitchen Printer
            $products = collect($transactionProducts)->whereIn('product_bid', collect($items)->pluck('product_uom_packaging_bid'));

            if (! empty($printerHost)) {
                $this->printKitchen($printerHost, $products, $transactions); // Call print directly
                //KDSPrintToKitchenPrinter::dispatch($printerHost, $products, $transactions); // Add kitchen printing on the queue
            }
        }
        // Call sticker printing when printable for stickers are present
        if (count($transactionStickersProducts) > 0) {
            $this->printSticker($printerHost, $transactionStickersProducts, $transactions);
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
        $result = null;
        $data = (object) stringToJson($request->all());
        if (! empty($data->filters)) {
            $result = app()->make(TerminalTransactionRepository::class)->list($data->filters);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        return $this->successfulResponse($result);
    }
}
