<?php

namespace App\Transformers;

use App\Entities\CatapultDbSetup;
use League\Fractal\TransformerAbstract;

class CatapultDbSetupTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(CatapultDbSetup $model)
    {
        return [
            'bid' => (string) $model->bid,
            'name' => (string) $model->name,
            'host' => (string) $model->host,
            'port' => (string) $model->port,
            'db_name' => (string) $model->db_name,
            'username' => (string) $model->username,
            'password' => (string) $model->password,
            'status' => (int) $model->status,
            'created_by' => $model->created_by,
            'updated_by' => $model->updated_by,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ];
    }
}
