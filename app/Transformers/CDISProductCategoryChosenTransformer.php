<?php

namespace App\Transformers;

use App\Entities\CDISProductCategory;
use League\Fractal\TransformerAbstract;

class CDISProductCategoryChosenTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(CDISProductCategory $model)
    {
        return [
            'bid' => $model->bid,
            'value' => $model->bid,
            'label' => $model->name,
            'text' => $model->name,
            'children' => $this->children($model->children)
        ];
    }

    public function children($data) {
        $dataReturn = fractal($data, CDISProductCategoryChosenTransformer::class);
        return $dataReturn->toArray()['data'];
    }
}
