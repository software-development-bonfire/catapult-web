<?php

namespace App\Transformers\Ecommerce;

use League\Fractal\TransformerAbstract;
use App\Entities\POSTerminalTransaction;

/**
 * Class ApiSetupTransformer.
 *
 * @package namespace App\Transformers;
 */
class CustomerEmailTransformer extends TransformerAbstract
{
    /**
     * Transform the ApiSetup entity.
     *
     * @param \App\Entities\POSTerminalTransaction $model
     *
     * @return array
     */
    public function transform(POSTerminalTransaction $model)
    {
        return [
            'bid' => $model->bid,
            'name' => $model->name,
            'order_number' => $model->order_number,
            'discount' => number_format((float) $model->discount_amount, 2, '.', ''),
            'delivery_fee' => number_format((float) $model->delivery_fee, 2, '.', ''),
            'sub_total' => number_format((float) $model->sub_total, 2, '.', ''),
            'total_amount' => number_format((float) $model->total_amount, 2, '.', ''),
            'email_address' => $model->email_address
        ];
    }
}
