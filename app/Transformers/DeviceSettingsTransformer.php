<?php

namespace App\Transformers;

use App\Entities\DeviceSettings;
use App\Enums\DeviceType;
use App\Enums\Status;
use League\Fractal\TransformerAbstract;

class DeviceSettingsTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param  DeviceSettings $model
     * @return array
     */
    public function transform(DeviceSettings $model)
    {
        return [
            'bid' => $model->bid,
            'terminal_code' => $model->terminal_code,
            'device_code' => $model->device_code,
            'device_type' => $model->device_type,
            'name' => $model->name,
            'ip_address' => $model->ip_address,
            'socket_status' => $model->socket_status,
            'print_invoice' => $model->print_invoice,
            'token' => $model->token,
            'status' => $model->status,
            'deleted_at' => $model->deleted_at,
        ];
    }
}
