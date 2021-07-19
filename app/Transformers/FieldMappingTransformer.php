<?php

namespace App\Transformers;

use App\Entities\FieldMappingList;
use League\Fractal\TransformerAbstract;

class FieldMappingTransformer extends TransformerAbstract
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
    public function transform(FieldMappingList $model)
    {
        $data = array();
            $data['bid'] = (int) $model->bid;
            $data['field_mapping_bid'] = (string) $model->field_mapping_bid;
            $data['remote_setup_bid'] = (string) $model->remote_setup_bid;
            $data['catapult_db_setup_bid'] = (string) $model->catapult_db_setup_bid;
            $data['api_setup_bid'] = (string) $model->api_setup_bid;
            $data['remote_setup_name'] = (string) $model->remoteSetup['name'];
            $data['catapult_db_setup_name'] = (string) $model->catapultDBSetup['name'];
            $data['api_setup_name'] = (string) $model->apiSetup['name'];
            $data['name'] = (string) $model->name;
            $data['mapping_type'] = (int) $model->type;
            $data['api_version_name'] = (string) $model->api_version_name;
            $data['status'] = (int) $model->status;
            $data['api_to_map'] = (string) $model->api_endpoint;
            
        return $data;
    }
}
