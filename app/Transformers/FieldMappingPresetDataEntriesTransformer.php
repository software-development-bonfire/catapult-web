<?php

namespace App\Transformers;

use App\Entities\FieldMappingPreset;
use League\Fractal\TransformerAbstract;

class FieldMappingPresetDataEntriesTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @param  FieldMappingPreset $model
     * @return array
     */
    public function transform(FieldMappingPreset $model)
    {
        $details = collect($model['detail'])->map(function($data){
            return [
                'bid' => $data->bid,
                'field_mapping_bid' => $data->field_mapping_bid,
                'required' => $data->required === 1 ? true : false,
                'nullable' => $data->nullable === 1 ? true : false,
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
            $data['data_entry'] = (string) $model->data_entry;
            $data['preset_name'] = (string) $model->preset_name;
            $data['details'] = $details;
        return $data;
    }
}
