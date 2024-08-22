<?php

namespace App\Http\Controllers\POS\v1;

use App\Enums\Status;
use App\Events\KioskTransactionEvent;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Services\POS\TerminalTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class TerminalTransactionController extends Controller
{

    public function store(Request $request)
    {
        $data = (object) stringToJson($request->all());
        if (! empty($data->data)) {
            $result = app()->make(TerminalTransactionService::class)->store($data->data);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        Log::alert(json_encode($data));
        broadcast(new KioskTransactionEvent($data));
        return $this->successfulResponse(
            $result,
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
            \Illuminate\Support\Facades\Log::alert(json_encode($data->filters));
            $result = app()->make(TerminalTransactionRepository::class)->list($data->filters);
        } else {
            return $this->errorResponse([], 'Missing request parameters');
        }

        return $this->successfulResponse($result);
    }
}
