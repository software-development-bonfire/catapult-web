<?php

namespace App\Transformers\CDIS\KitchenStation;

use App\Entities\CDISKitchenStation;
use League\Fractal\TransformerAbstract;

/**
 * Class ListTransformer.
 *
 * @package namespace App\Transformers\CDIS\KitchenStation;
 */
class ListTransformer extends TransformerAbstract
{
    /**
     * Transform the ApiSetup entity.
     *
     * @param CDISKitchenStation $model
     *
     * @return array
     */
    public function transform(CDISKitchenStation $model)
    {
        $data = [
            'bid' => (string) $model->bid,
            'code' => $model->code,
            'name' => $model->name,
            'queueing_group_type' => $model->queueing_group_type,
            'screen_prioritization' => $model->screen_prioritization,
            'status' => $model->status,
        ];

        if ($model->relationLoaded('boundDevice')) {
            $device = $model->boundDevice;
            $data['bound_device'] = $device ? [
                'bid'        => (string) $device->bid,
                'device_uid' => $device->device_uid,
                'device_code'=> $device->device_code,
                'name'       => $device->name,
                'status'     => $device->status,
            ] : null;
        }

        return $data;
    }
}
