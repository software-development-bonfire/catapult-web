<?php

namespace App\Transformers\KDS\KitchenDisplay;

use App\Entities\KitchenDisplayDetail;
use League\Fractal\TransformerAbstract;

/**
 * Class AddonListTransformer.
 *
 * @package namespace App\Transformers\KDS\KitchenDisplay;
 */
class AddonListTransformer extends TransformerAbstract
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
            'product_bid' => (string) $model->product_bid,
            'name' => $model->name,
            'quantity' => number_format($model->quantity, $decimal)
        ];
    }
}
