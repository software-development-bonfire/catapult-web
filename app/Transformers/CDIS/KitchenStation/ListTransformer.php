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
        return [
            'bid' => (string) $model->bid,
            'code' => $model->code,
            'name' => $model->name,
            'queueing_group_type' => $model->queueing_group_type,
            'screen_prioritization' => $model->screen_prioritization,
            'status' => $model->status
        ];
    }
}
