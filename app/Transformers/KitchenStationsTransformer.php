<?php

namespace App\Transformers;

use App\Entities\CDISKitchenStation;
use League\Fractal\TransformerAbstract;

class KitchenStationsTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param  CDISKitchenStation $model
     * @return array
     */
    public function transform($model)
    {
        return [
            'value' => (string) $model->bid,
            'code' => $model->code,
            'label' => $model->name,
        ];
    }
}
