<?php

namespace App\Transformers\KDS;

use App\Entities\KitchenDisplayDetail;
use League\Fractal\TransformerAbstract;

/**
 * Class ItemListTransformer.
 *
 * @package namespace App\Transformers\KDS\KitchenDisplay;
 */
class ItemListTransformer extends TransformerAbstract
{
    /**
     * Transform the ApiSetup entity.
     *
     * @param KitchenDisplayDetail $model
     *
     * @return array
     */
    public function transform(KitchenDisplayDetail $model)
    {
        $decimal = config('decimal.places');

        return [
            'transaction_id' => $model->kitchen_display_detail_status,
            'status' => $model->kitchen_display_detail_status,
            'special_request' => $model->special_request,
            'code' => $model->code,
            'name' => $model->name,
            'barcode' => $model->barcode,
            'description' => $model->description,
            'long_description' => $model->long_description,
        ];
    }
}
