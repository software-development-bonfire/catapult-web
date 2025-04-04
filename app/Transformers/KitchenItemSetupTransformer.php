<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class KitchenItemSetupTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param  object $model
     * @return array
     */
    public function transform($model)
    {
        return [
            'value' => (string) $model->bid,
            'code' => $model->code,
            'label' => $model->name,
            "head_bid" => (string) $model->head_bid,
            "kitchen_station_process_bid" => $model->kitchen_station_process_bid,
            "product_uom_packaging_bid" => $model->product_uom_packaging_bid,
            "code" => $model->code,
            "device_code" => $model->device_code,
            "device_uid" => $model->device_uid,
            "device_name" => $model->device_name
        ];
    }
}
