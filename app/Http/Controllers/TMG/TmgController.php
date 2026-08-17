<?php

namespace App\Http\Controllers\TMG;

use App\Http\Controllers\Controller;
use App\Services\TMG\TmgService;
use Illuminate\Http\Request;

class TmgController extends Controller
{
    protected $service;

    public function __construct(TmgService $service)
    {
        $this->service = $service;
    }

    public function login(Request $request)
    {
        $passcode = $request->get('passcode');
        $deviceUid = $request->get('device_uid');
        $kitchenStationBid = $request->get('kitchen_station_bid');

        if (!$passcode || !$kitchenStationBid) {
            return $this->errorResponse([], 'Passcode and kitchen station are required.');
        }

        $result = $this->service->login($passcode, $deviceUid, $kitchenStationBid);

        if (!$result['success']) {
            return $this->errorResponse([], $result['message']);
        }

        return $this->successfulResponse($result['data'], 'Login successful.');
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        $this->service->logout($token);

        return $this->successfulResponse(null, 'Logged out successfully.');
    }

    public function transactions(Request $request)
    {
        $kitchenStationBid = $request->get('kitchen_station_bid');
        $status = $request->get('status');
        $orderType = $request->get('order_type');

        $result = $this->service->getTransactions($kitchenStationBid, $status, $orderType);

        return $this->successfulResponse($result);
    }

    public function transactionDetail(Request $request, $headBid)
    {
        $result = $this->service->getTransactionDetail($headBid);

        if (!$result) {
            return $this->noEntryFoundResponse([], 'Transaction not found.');
        }

        return $this->successfulResponse($result);
    }

    public function action(Request $request)
    {
        $action = $request->get('action');
        $payload = $request->get('payload', []);

        if (!$action) {
            return $this->errorResponse([], 'Action is required.');
        }

        $result = $this->service->handleAction($action, $payload);

        if (!$result['success']) {
            return $this->errorResponse([], $result['message']);
        }

        return $this->successfulResponse($result['data'], $result['message']);
    }

    public function kitchenStations(Request $request)
    {
        $result = $this->service->getKitchenStations();

        return $this->successfulResponse($result);
    }

    public function summary(Request $request)
    {
        $kitchenStationBid = $request->get('kitchen_station_bid');
        $result = $this->service->getSummary($kitchenStationBid);

        return $this->successfulResponse($result);
    }

    public function showLogin()
    {
        return view('tmg.login');
    }

    public function showDisplay()
    {
        return view('tmg.display');
    }
}
