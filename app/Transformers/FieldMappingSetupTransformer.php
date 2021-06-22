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
        $details = [];
        $details = collect($model['details'])->map(function($data){
            return [
            'bid' => $data->bid,
            'field_mapping_bid' => $data->field_mapping_bid,
            'required' => $data->required === 1 ? true : false,
            'field' => $data->field,
            'description' => $data->description,
            'mapping_type' => $data->mapping_type,
            'file_name' => $data->file_name,
            'default_value' => $data->default_value,
            'column_name' => $data->column_name,
            ];
        });
        $data = array();
            $data['bid'] = (int) $model->bid;
            $data['mapping_type'] = (int) $model->type;
            $data['api_endpoint'] = (string) $model->api_endpoint;
            $data['api_version_name'] = (string) $model->api_version_name;
            $data['total_field_entries'] = (int) $model->details_count;
            $data['status'] = (int) $model->status;
            $data['last_modified'] = $model->updated_at;
            $data['details'] = $details;
        return $data;
    }
}
