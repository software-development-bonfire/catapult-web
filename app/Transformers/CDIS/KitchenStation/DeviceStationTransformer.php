<?php

namespace App\Transformers\CDIS\KitchenStation;

use League\Fractal\TransformerAbstract;

/**
 * Class DeviceStationTransformer.
 *
 * @package namespace App\Transformers\CDIS\KitchenStation;
 */
class DeviceStationTransformer extends TransformerAbstract
{
    /**
     * Transform the kitchen station device
     *
     * @param object $model
     *
     * @return array
     */
    public function transform($model)
    {
        return [
            'bid' => (string) $model->bid,
            'code' => $model->code,
            'name' => $model->name,
            'order_type' => $model->order_type,
            'queueing_group_type' => $model->queueing_group_type,
            'screen_prioritization' => $model->screen_prioritization,
            'status' => $model->status
        ];
    }
}
