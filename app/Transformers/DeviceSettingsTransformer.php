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
            'device_type' => $model->device_type,
            'name' => $model->name,
            'ip_address' => $model->ip_address,
            'api_endpoint' => $model->api_endpoint,
            'token' => $model->token,
            'status' => $model->status,
        ];
    }
}
