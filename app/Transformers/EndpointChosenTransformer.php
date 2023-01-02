<?php

namespace App\Transformers;

use App\Entities\ApiSetup;
use League\Fractal\TransformerAbstract;

class EndpointChosenTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     * @param ApiSetup $model
     * @return array
     */
    public function transform(ApiSetup $model)
    {
        return [
            'value' => (string) $model->bid,
            'label' => (string) $model->name,
            'endpoint_url' => (string) $model->end_point,
        ];
    }
}
