<?php

namespace App\Http\Controllers\POS\v1;

use App\Http\Controllers\POS\POSBaseController;
use App\Services\POS\TableManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableManagementController extends POSBaseController
{
    protected $tableManagementService;

    public function __construct(TableManagementService $tableManagementService)
    {
        $this->tableManagementService = $tableManagementService;
    }

    public function upsertLocation(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'id' => 'nullable|integer|min:1',
            'name' => 'required|string|max:255',
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->tableManagementService->upsertLocation($validator->validated());

        return $this->successfulResponse($result, 'Table location saved successfully');
    }

    public function upsertTable(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'id' => 'nullable|integer|min:1',
            'location_id' => 'nullable|integer|min:1|exists:table_location,id',
            'name' => 'required|string|max:255',
            'status' => 'nullable|integer',
            'availability' => 'nullable|in:' . implode(',', $this->tableManagementService->getTableAvailabilityValues()),
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->tableManagementService->upsertTable($validator->validated());

        return $this->successfulResponse($result, 'Table saved successfully');
    }

    public function updateTableAvailability(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'id' => 'nullable|integer|min:1|required_without:name',
            'name' => 'nullable|string|max:255|required_without:id',
            'location_id' => 'nullable|integer|min:1',
            'availability' => 'required|in:' . implode(',', $this->tableManagementService->getTableAvailabilityValues()),
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->tableManagementService->updateTableAvailability($validator->validated());

        if (!$result) {
            return $this->errorResponse([], 'Table not found');
        }

        return $this->successfulResponse($result, 'Table availability updated successfully');
    }

    public function checkTableAvailability(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'id' => 'nullable|integer|min:1|required_without:name',
            'name' => 'nullable|string|max:255|required_without:id',
            'location_id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->tableManagementService->checkTableAvailability($validator->validated());

        if (!$result) {
            return $this->errorResponse([], 'Table not found');
        }

        return $this->successfulResponse($result);
    }

    public function updateTransactionAvailability(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'bid' => 'nullable|numeric|required_without:transaction_id',
            'transaction_id' => 'nullable|numeric|required_without:bid',
            'availability' => 'required|in:' . implode(',', $this->tableManagementService->getTransactionAvailabilityValues()),
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->tableManagementService->updateTransactionAvailability($validator->validated());

        if (!$result) {
            return $this->errorResponse([], 'Transaction not found');
        }

        return $this->successfulResponse($result, 'Transaction availability updated successfully');
    }

    public function checkTransactionAvailability(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'bid' => 'nullable|numeric|required_without:transaction_id',
            'transaction_id' => 'nullable|numeric|required_without:bid',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
        }

        $result = $this->tableManagementService->checkTransactionAvailability($validator->validated());

        if (!$result) {
            return $this->errorResponse([], 'Transaction not found');
        }

        return $this->successfulResponse($result);
    }

    private function extractPayload(Request $request): array
    {
        $payload = (object) stringToJson($request->all());

        if (isset($payload->data) && is_array($payload->data)) {
            return $payload->data;
        }

        if (isset($payload->data) && is_object($payload->data)) {
            return (array) $payload->data;
        }

        return (array) $payload;
    }
}
