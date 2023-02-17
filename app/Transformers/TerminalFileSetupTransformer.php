<?php

namespace App\Transformers;

use App\Entities\TerminalFileSetup;
use League\Fractal\TransformerAbstract;

class TerminalFileSetupTransformer extends TransformerAbstract
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
    public function transform(TerminalFileSetup $model)
    {
        return [
            'bid' => (string) $model->bid,
            'name' => (string) $model->name,
            'type' => $model->type,
            'endpoint' => [
                'value' => $model->apiSetup->bid,
                'label' => $model->apiSetup->name,
                'endpoint_url' => $model->apiSetup->end_point,
            ],
            'api_setup_bid' => (string) $model->api_setup_bid,
            'terminal_code' => (string) $model->terminal_code,
            'terminal_path' => (string) $model->terminal_path,
            'sub_directories' => (string) $model->sub_directories,
            'status' => (int) $model->status,
            'created_by' => $model->created_by,
            'updated_by' => $model->updated_by,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ];
    }
}
