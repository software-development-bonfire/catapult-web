<?php

namespace App\Transformers;

use App\Entities\FieldMapping;
use League\Fractal\TransformerAbstract;

class FieldMappingTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param FieldMapping $model
     * @return array
     */
    public function transform(FieldMapping $model)
    {
        $data = array();
            $data['bid'] = (string) $model->bid;
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
            $data['data_entry'] = (string) $model->data_entry;
            
        return $data;
    }
}
