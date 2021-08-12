<?php

namespace App\Transformers;

use App\Entities\SystemLog;
use League\Fractal\TransformerAbstract;

class SystemLogTransformer extends TransformerAbstract
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
    public function transform(SystemLog $model)
    {
        return [
            'bid' => (int) $model->bid,
            'initiator' => (string) $model->initiator,
            'module_or_process' => (string) $model->module_process,
            'action' => (string) $model->action,
            'logs_description' => (string) $model->description,
            'timestamp' => $model->created_at
        ];
    }
}
