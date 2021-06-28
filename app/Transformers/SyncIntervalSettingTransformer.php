<?php

namespace App\Transformers;

use App\Entities\SyncIntervalSetting;
use League\Fractal\TransformerAbstract;

class SyncIntervalSettingTransformer extends TransformerAbstract
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
    public function transform(SyncIntervalSetting $model)
    {
        return [
            'id' => (string) $model->id,
            'bid' => (string) $model->bid,
            'name' => (string) $model->name,
            'checking_interval' => (string) $model->checking_interval,
            'syncing_type' => (int) $model->syncing_type,
            'start_time' => (string) $model->start_time,
            'status' => (int) $model->status,
            'created_by' => $model->created_by,
            'updated_by' => $model->updated_by
        ];
    }
}
