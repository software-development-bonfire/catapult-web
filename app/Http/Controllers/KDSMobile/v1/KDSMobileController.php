<?php

namespace App\Http\Controllers\KDSMobile\v1;

use App\Http\Controllers\Controller;
use App\Services\KDSMobile\KDSMobileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KDSMobileController extends Controller
{
    private KDSMobileService $service;

    public function __construct(KDSMobileService $service)
    {
        $this->service = $service;
    }

    /**
     * Get transactions list with filters.
     * GET /api/kds-mobile/v1/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $filters = [
            'branch_bid' => $request->get('branch_bid'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'delay_minutes' => $request->get('delay_minutes', 10),
            'on_going_delay_minutes' => $request->get('on_going_delay_minutes', 5),
        ];

        $transactions = $this->service->getTransactions($filters);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get transaction detail.
     * GET /api/kds-mobile/v1/transactions/{transactionId}
     */
    public function transactionDetail(Request $request, string $transactionId): JsonResponse
    {
        $detail = $this->service->getTransactionDetail($transactionId);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }

    /**
     * Get summary counts (for tab badges).
     * GET /api/kds-mobile/v1/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = [
            'branch_bid' => $request->get('branch_bid'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'delay_minutes' => $request->get('delay_minutes', 10),
            'on_going_delay_minutes' => $request->get('on_going_delay_minutes', 5),
        ];

        $summary = $this->service->getSummary($filters);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get available branches.
     * GET /api/kds-mobile/v1/branches
     */
    public function branches(): JsonResponse
    {
        $branches = $this->service->getBranches();

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    /**
     * Get pusher config for the mobile monitor.
     * GET /api/kds-mobile/v1/config
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'pusher' => [
                    'app_key' => config('broadcasting.connections.pusher.key'),
                    'host' => request()->getHost(),
                    'port' => config('broadcasting.connections.pusher.options.port', 6001),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster', 'mt1'),
                    'encrypted' => false,
                ],
            ],
        ]);
    }
}
