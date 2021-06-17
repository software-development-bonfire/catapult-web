<?php

namespace App\Transformers;

use App\Entities\FieldMapping;
use League\Fractal\TransformerAbstract;

class FieldMappingSetupTransformer extends TransformerAbstract
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
    public function transform(FieldMapping $model)
    {
        return [
            'bid' => (int) $model->bid,
            'mapping_type' => (int) $model->type,
            'api_endpoint' => (string) $model->api_endpoint,
            'api_version_name' => (string) $model->api_version_name,
            'total_field_entries' => (int) $model->details_count,
            'status' => (int) $model->status,
            'last_modified' => $model->updated_at,
            'details' => $model->details
        ];
    }
}
