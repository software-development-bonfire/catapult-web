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

    public function getTables(Request $request)
    {
        $locationId = $request->query('location_id');
        
        /**
         * STATION OTS ID SWAPPING REQUEST PARAMETER
         * 
         * The swap_id parameter enables Station OTS to receive table data with POS IDs
         * as the primary "id" field, allowing proper data mapping and state management.
         * 
         * Accepts swap_id from either:
         * - Request body (JSON): {"app_id": "pos", "app_key": "...", "data": {"swap_id": true}}
         * - Query parameter: ?swap_id=1 or ?swap_id=true
         * 
         * When swap_id=true:
         * - Catapult DB ID is moved to "catapult_table_id"
         * - POS DB ID (pos_table_id) becomes the primary "id" field
         * - This matches Station OTS expectations for table identification
         */
        $swapId = false;
        $requestData = $request->all();
        
        // Check for swap_id in nested data (from JSON body)
        if (isset($requestData['data'])) {
            $data = $requestData['data'];
            // Handle both array and object formats
            if (is_array($data) && isset($data['swap_id'])) {
                $swapId = (bool) $data['swap_id'];
            } elseif (is_object($data) && isset($data->swap_id)) {
                $swapId = (bool) $data->swap_id;
            }
        }
        
        // Fallback to query parameter if swap_id not found in body
        if (!$swapId && $request->query('swap_id')) {
            $swapId = (bool) $request->query('swap_id');
        }

        $tables = $this->tableManagementService->getTables($locationId, false, $swapId);

        return $this->successfulResponse($tables, 'Tables fetched successfully');
    }

    public function getLocations(Request $request)
    {
        /**
         * STATION OTS ID SWAPPING REQUEST PARAMETER
         * 
         * The swap_id parameter enables Station OTS to receive location data with POS IDs
         * as the primary "id" field, allowing proper location-to-table mapping and sync.
         * 
         * Accepts swap_id from either:
         * - Request body (JSON): {"app_id": "pos", "app_key": "...", "data": {"swap_id": true}}
         * - Query parameter: ?swap_id=1 or ?swap_id=true
         * 
         * When swap_id=true:
         * - Catapult DB ID is moved to "catapult_location_id"
         * - POS DB ID (pos_location_id) becomes the primary "id" field
         * - This ensures Station OTS can reliably identify and reference locations
         * - All entity properties (location_name, no_of_tables, no_of_seats, status) are preserved
         */
        $swapId = false;
        $requestData = $request->all();
        
        // Check for swap_id in nested data (from JSON body)
        if (isset($requestData['data'])) {
            $data = $requestData['data'];
            // Handle both array and object formats
            if (is_array($data) && isset($data['swap_id'])) {
                $swapId = (bool) $data['swap_id'];
            } elseif (is_object($data) && isset($data->swap_id)) {
                $swapId = (bool) $data->swap_id;
            }
        }
        
        // Fallback to query parameter if swap_id not found in body
        if (!$swapId && $request->query('swap_id')) {
            $swapId = (bool) $request->query('swap_id');
        }

        $locations = $this->tableManagementService->getLocations(false, $swapId);

        return $this->successfulResponse($locations, 'Locations fetched successfully');
    }

    public function upsertLocation(Request $request)
    {
        $payload = (object) stringToJson($request->all());

        if (!$this->isValidCatapultKey($request)) {
            return $this->errorResponse([], 'Invalid app_key');
        }

        if (!isset($payload->data) || !is_array($payload->data)) {
            return $this->errorResponse([], 'Missing data array');
        }

        foreach ($payload->data as $data) {
            $validator = Validator::make((array) $data, [
                'id' => 'required|integer|min:0',
                'location_name' => 'required|string|max:255',
                'no_of_tables' => 'nullable|integer|min:0',
                'no_of_seats' => 'nullable|integer|min:0',
                'status' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
            }
        }

        $results = $this->tableManagementService->upsertLocationBatch($payload->data);
        return $this->successfulResponse($results, 'Table locations saved successfully');
    }

    public function upsertTable(Request $request)
    {
        $payload = (object) stringToJson($request->all());

        if (!$this->isValidCatapultKey($request)) {
            return $this->errorResponse([], 'Invalid app_key');
        }

        if (!isset($payload->data) || !is_array($payload->data)) {
            return $this->errorResponse([], 'Missing data array');
        }

        foreach ($payload->data as $data) {
            $validator = Validator::make((array) $data, [
                'id' => 'required|integer|min:0',
                'location_id' => 'required|integer|min:1',
                'table_ref' => 'required|string|max:255',
                'seat_number' => 'required|integer|min:0',
                'status' => 'required|integer',
                'is_available' => 'required|integer',
                'position_x' => 'nullable|numeric',
                'position_y' => 'nullable|numeric',
                'width' => 'nullable|numeric',
                'height' => 'nullable|numeric',
                'angle' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->toArray(), 'Invalid request parameters');
            }
        }

        $results = $this->tableManagementService->upsertTableBatch($payload->data);
        return $this->successfulResponse($results, 'Tables saved successfully');
    }

    public function updateTableAvailability(Request $request)
    {
        $data = $this->extractPayload($request);

        $validator = Validator::make($data, [
            'id' => 'nullable|integer|min:1|required_without:name',
            'name' => 'nullable|string|max:255|required_without:id',
            'location_id' => 'required|integer|min:1',
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
}
