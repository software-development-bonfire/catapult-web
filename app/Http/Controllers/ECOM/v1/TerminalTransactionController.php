<?php

namespace App\Http\Controllers\ECOM\v1;

use App\Events\TransactionEvent;
use App\Http\Controllers\ECOM\EcomBaseController;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Services\ECOM\EcomTerminalTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Enums\API\DeviceType;
use Illuminate\Support\Facades\Log;

class TerminalTransactionController extends EcomBaseController
{

    public function store($data)
    {
        if (! empty($data)) {
            $data = json_decode($data);
            $result = app()->make(EcomTerminalTransactionService::class)->store($data->orderInformation->data, DeviceType::ECOMMERCE);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        return $this->successfulResponse(
            $data,
            Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
        );
    }

    public function update($data)
    {
        if (! empty($data)) {
            $data = json_decode($data);
            $result = app()->make(EcomTerminalTransactionService::class)->updateOrder($data);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        return $this->successfulResponse(
            $data,
            Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
        );
    }
}
