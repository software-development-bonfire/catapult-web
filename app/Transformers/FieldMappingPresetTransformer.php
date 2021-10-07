<?php

namespace App\Transformers;

use App\Entities\FieldMappingPreset;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class FieldMappingPresetTransformer extends TransformerAbstract
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
    public function transform(FieldMappingPreset $model)
    {
        $data = array();
            $data['bid'] = (string) $model->bid;
            $data['mapping_type'] = (int) $model->type;
            $data['data_entry'] = (string) $model->data_entry;
            $data['preset_name'] = (string) $model->preset_name;
            $data['total_field_entries'] = (int) $model->detail_count;
            $data['status'] = (int) $model->status;
            $data['last_modified'] = Carbon::parse($model->updated_at)->format('Y-m-d h:i:s');
        return $data;
    }
}
