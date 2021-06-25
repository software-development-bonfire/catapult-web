<?php

namespace App\Transformers;

use App\User;
use League\Fractal\TransformerAbstract;

class UserAccountTransformer extends TransformerAbstract
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
    public function transform(User $model)
    {
        $permission = [];

        $permission = collect($model['permissions'])->map(function ($data) {
            return [
                'code' => $data->code,
            ];
        });
        return [
            'id' => (string) $model->id,
            'bid' => (string) $model->bid,
            'name' => (string) $model->name,
            'username' => (string) $model->username,
            'password' => (string) $model->password,
            'status' => (int) $model->status,
            'permission' => $permission,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ];
    }
}
