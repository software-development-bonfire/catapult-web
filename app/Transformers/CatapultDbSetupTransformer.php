<?php

namespace App\Transformers;

use App\Entities\CatapultDbSetup;
use League\Fractal\TransformerAbstract;

class CatapultDbSetupTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        //
    ];
    
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];
    
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(CatapultDbSetup $model)
    {
        return [
            'id' => (int) $model->id,
            'bid' => (int) $model->bid,
            'name' => (string) $model->name,
            'host' => (string) $model->host,
            'port' => (string) $model->port,
            'db_name' => (string) $model->db_name,
            'username' => (string) $model->username,
            'status' => (string) $model->status == 1 ? 'Active' : 'Inactive',
            'created_by' => $model->created_by,
            'updated_by' => $model->updated_by,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ];
    }
}
