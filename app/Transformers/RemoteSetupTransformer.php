<?php

namespace App\Transformers;

use App\Entities\RemoteSetup;
use App\Enums\StorageType;
use League\Fractal\TransformerAbstract;

class RemoteSetupTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(RemoteSetup $model)
    {
        return [
            'bid' => (string) $model->bid,
            'name' => (string) $model->name,
            'storage_type_label' => StorageType::getDescription($model->storage_type),
            'storage_type' => $model->storage_type,
            'local_path' => (string) $model->local_path,
            'remote_path' => (string) $model->remote_path,
            'server' => (string) $model->server,
            'host' => (string) $model->host,
            'port' => (string) $model->port,
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
