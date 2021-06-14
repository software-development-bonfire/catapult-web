<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Entities\ApiSetup;

/**
 * Class ApiSetupTransformer.
 *
 * @package namespace App\Transformers;
 */
class ApiSetupTransformer extends TransformerAbstract
{
    /**
     * Transform the ApiSetup entity.
     *
     * @param \App\Entities\ApiSetup $model
     *
     * @return array
     */
    public function transform(ApiSetup $model)
    {
        return [
            'bid' => (int) $model->bid,
            'name' => (string) $model->name,
            'end_point' => (string) $model->end_point,
            'status' => (int) $model->status,
            'created_by' => $model->created_by,
            'updated_by' => $model->updated_by,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ];
    }
}
