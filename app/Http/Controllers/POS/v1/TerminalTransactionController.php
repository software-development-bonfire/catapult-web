<?php

namespace App\Http\Controllers\POS\v1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\POS\TerminalTransactionRepository;
use App\Services\POS\TerminalTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

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

        return $this->successfulResponse(
            $result,
            Lang::get('success.successfully_created', ['value' => __('label.terminal_transaction')])
        );
    }

    public function list(Request $request)
    {
        $filters = stringToJson($request->get('filters'));
        $transactions = app()->make(TerminalTransactionRepository::class)->list($filters);

        $data = [];
        foreach ($transactions as $transaction) {
            $transaction = (object) $transaction;
            $details = app()->make(TerminalTransactionRepository::class)->details((object) [
                'terminal_transaction_bid' => $transaction->transaction_id
            ]);
            $transaction->details = $details;
        }

        return $this->successfulResponse($transactions);
    }
}
