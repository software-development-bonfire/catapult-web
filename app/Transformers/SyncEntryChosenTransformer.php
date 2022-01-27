<?php

namespace App\Transformers;

use App\Entities\SyncEntry;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class SyncEntryChosenTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     * @param SyncEntry $model
     * @return array
     */
    public function transform(SyncEntry $model)
    {
        return [
            'value' => $model->name,
            'label' => Str::title(str_replace('_', ' ', $model->name)),
        ];
    }
}
