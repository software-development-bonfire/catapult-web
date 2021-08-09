<?php

namespace App\Transformers;

use App\Entities\ErrorLog;
use League\Fractal\TransformerAbstract;

class ErrorLogTransformer extends TransformerAbstract
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
    public function transform(ErrorLog $model)
    {
        $details = [];
    
        $details = collect($model['details'])->map(function($data) {
            return [
                'bid' => $data->bid,
                'error_log_bid' => $data->error_log_bid,
                'sheet' => $data->sheet,
                'error_type' => $data->error_type,
                'description' => $data->description,
            ];
        });

        $data = array();
        $data['bid'] = (int) $model->bid;
        $data['pos_entry'] = (string) $model->pos_entry;
        $data['csv_file'] = (string) $model->filename;
        $data['path'] = (string) $model->path;
        $data['date_detected'] = (string) $model->created_at;
        $data['status'] = $model->status == 'Resolved' ? 1 : 0;
        $data['details'] = $details;

        return $data;
    }
}
