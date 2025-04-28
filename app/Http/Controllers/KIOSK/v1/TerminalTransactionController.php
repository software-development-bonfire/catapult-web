<?php

namespace App\Http\Controllers\KIOSK\v1;

use App\Enums\API\DeviceType;
use App\Events\TransactionEvent;
use App\Http\Controllers\KIOSK\KioskBaseController;
use App\Repositories\Contracts\DeviceSettingsRepository;
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
            $result = app()->make(KioskTerminalTransactionService::class)->store($data->device_code, $data->data, DeviceType::KIOSK);
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
                    $devices = app()->make(DeviceSettingsRepository::class)->getActivePOS();
                    if (count($devices) > 0) {
                        foreach ($devices as $device) {
                            // Send to the online POS device with first priority
                            broadcast(new TransactionEvent($data->device_code, $device['device_code'], $data->data));
                            break;
                        }
                    } else {
                        // @TODO: If no online/configured devices then add to QUEUE
                        // and report back to KIOSK device to inform the status
                    }
                }
            }
        }
        return $this->successfulResponse(
            $data->data,
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
