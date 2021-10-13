<?php

namespace App\Transformers;

use App\Entities\FieldMapping;
use League\Fractal\TransformerAbstract;

class FieldMappingDetailTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param  FieldMapping $model
     * @return array
     */
    public function transform(FieldMapping $model)
    {
        $details = collect($model['detail'])->map(function($data){
            return [
                'bid' => (string) $data->bid,
                'field_mapping_bid' => (string) $data->field_mapping_bid,
                'required' => $data->required === 1 ? true : false,
                'field' => $data->field,
                'description' => $data->description,
                'mapping_type' => $data->mapping_type,
                'file_name' => $data->file_name,
                'default_value' => $data->default_value,
                'column_name' => $data->column_name,
                'reference_column_name' => $data->reference_column_name,
                'head_reference' => $data->head_reference,
            ];
        });

        $data = array();
            $data['bid'] = (string) $model->bid;
            $data['file_storage_setup_bid'] = (string) $model->file_storage_setup_bid;
            $data['file_storage_setup_name'] = $model->fileStorageSetup->name;
            $data['api_setup_bid'] = (string) $model->api_setup_bid;
            $data['api_setup_name'] = $model->apiSetup->name;
            $data['catapult_db_setup_bid'] = (string) $model->catapult_db_setup_bid;
            $data['catapult_db_setup_name'] = $model->catapultDBSetup->name;
            $data['name'] = $model->name;
            $data['mapping_type'] = $model->type;
            $data['status'] = $model->status;
            $data['data_entry'] = $model->data_entry;
            $data['preset_name'] = $model->preset_name;
            $data['detail'] = $details;

        return $data;
    }
}
