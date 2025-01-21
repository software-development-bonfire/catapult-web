<?php

namespace App\Http\Controllers\POS\v1;

use App\Events\TransactionEvent;
use App\Helpers\IP;
use App\Http\Controllers\POS\POSBaseController;
use App\Jobs\KDS\PrintToKitchenPrinter as KDSPrintToKitchenPrinter;
use App\Repositories\Contracts\KitchenPrinterRepository;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Services\POS\TerminalTransactionService;
use App\Traits\KitchenDisplayTrait;
use App\Traits\KitchenPrinterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class TerminalTransactionController extends POSBaseController
{
    use KitchenDisplayTrait;
    use KitchenPrinterTrait;


    public function store(Request $request)
    {
        $transactions = [];
        if (! empty($request->transaction)) {
            $transactions = app()->make(TerminalTransactionService::class)->store($request->transaction);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        unset($request->access_token);

        $kitchenTransactions = app()->make(KitchenPrinterRepository::class)->getMenuPrinters(stringToJson($transactions));

        $groupedPrinters = collect($kitchenTransactions)->groupBy('local_printer');
        foreach ($groupedPrinters->toArray() as $printerHost => $items) {
            //$this->printKitchen($printerHost, $items, $transactions);
            KDSPrintToKitchenPrinter::dispatch($printerHost, $items, $transactions);
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
