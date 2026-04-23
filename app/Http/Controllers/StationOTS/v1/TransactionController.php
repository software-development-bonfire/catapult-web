<?php

namespace App\Http\Controllers\StationOTS\v1;

use App\Http\Controllers\StationOTS\StationOTSBaseController;
use App\Services\StationOTS\TransactionService;
use App\Traits\APIRequestTrait;
use App\Traits\TokenResponsesJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends StationOTSBaseController
{
    use TokenResponsesJson, APIRequestTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function store(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'terminal_bid' => 'required',
            'transaction_id' => 'required',
            'products' => 'nullable|array',
            'products.*.product_bid' => 'required',
            'products.*.name' => 'required|string',
            'products.*.quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->transactionService->store($data);

        return $this->successfulResponse($result, 'Transaction stored successfully');
    }
}
