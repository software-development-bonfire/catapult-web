<?php

namespace App\Http\Controllers\KIOSK\v1;

use App\Events\TransactionEvent;
use App\Http\Controllers\KIOSK\KioskBaseController;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Services\KIOSK\KioskTerminalTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class TerminalTransactionController extends KioskBaseController
{

    public function store(Request $request)
    {
        $data = (object) stringToJson($request->all());
        if (! empty($data->data)) {
            $result = app()->make(KioskTerminalTransactionService::class)->store($data->device_code, $data->data);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        unset($data->access_token);
        // Send transaction to POS if there is payment in the OTS
        if (isset($data->data['payments'])) {
            $payments = $data->data['payments'];
            if (isset($payments[0])) {
                $payment = (object) stringToJson($payments[0]);
                if ($payment->title !== 'CASH') {
                    broadcast(new TransactionEvent($data));
                }
            }
        }
        return $this->successfulResponse(
            $data,
            Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
        );
    }

    public function update(Request $request)
    {
        $data = (object) stringToJson($request->all());
        if (! empty($data->data)) {
            $result = app()->make(KioskTerminalTransactionService::class)->updateStatus($data->data);
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
