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
        if (!$this->isValidCatapultKey($request)) {
            return $this->errorResponse([], 'Invalid app_key');
        }
        
        $data = $this->extractPayload($request);

        $data['device_code'] = $request->input('device_code') ?? ($data['device_code'] ?? null);

        $validator = Validator::make($data, [
            'terminal_bid' => 'required',
            'transaction_id' => 'required',
            'details' => 'nullable|array',
            'details.*.product_bid' => 'required',
            'details.*.name' => 'required|string',
            'details.*.quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->transactionService->store($data);

        return $this->successfulResponse($result, 'Transaction stored successfully');
    }
}
