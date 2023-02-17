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
            $data['file_storage_setup_bid'] = (string) $model->file_storage_setup_bid;
            $data['catapult_db_setup_bid'] = (string) $model->catapult_db_setup_bid;
            $data['api_setup_bid'] = (string) $model->api_setup_bid;
            $data['file_storage_setup_name'] = (string) $model->fileStorageSetup['name'];
            $data['catapult_db_setup_name'] = (string) $model->catapultDBSetup['name'];
            $data['api_setup_name'] = (string) $model->apiSetup['name'];
            $data['name'] = (string) $model->name;
            $data['mapping_type'] = (int) $model->type;
            $data['api_version_name'] = (string) $model->api_version_name;
            $data['status'] = (int) $model->status;
            $data['is_customized_mapping'] = (int) $model->is_customized_mapping;
            $data['data_entry'] = (string) $model->data_entry;
            $data['primary_table'] = (string) $model->primary_table;

        return $data;
    }
}
