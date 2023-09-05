<?php

namespace App\Transformers;

use App\Entities\ErrorLog;
use League\Fractal\TransformerAbstract;

class ErrorLogTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(ErrorLog $model)
    {
        $message = '';
        if (!empty($model->details)) {
            $detail = (object) $model->details[0];
            $message = "[{$detail->error_type}]: {$detail->description}";
        }
        return [
            'bid' => (string) $model->bid,
            'filename' => $model->filename,
            'path' => $model->path,
            'status' => $model->status,
            'log_type' => $model->status,
            'date' => parseDateTime($model->created_at, 'Y-m-d h:i:s A'),
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
            'message' => $message,
            'details' => $model->details
        ];
    }
}
