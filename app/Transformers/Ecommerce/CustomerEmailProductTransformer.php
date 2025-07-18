<?php

namespace App\Transformers\Ecommerce;

use League\Fractal\TransformerAbstract;
use App\Entities\POSTerminalTransactionProduct;
use App\Repositories\Contracts\Ecommerce\PosTerminalTransactionRepository;
use Illuminate\Support\Facades\Log;

/**
 * Class ApiSetupTransformer.
 *
 * @package namespace App\Transformers;
 */
class CustomerEmailProductTransformer extends TransformerAbstract
{
    protected $isProduct;

    public function __construct($isProduct)
    {
        $this->isProduct = $isProduct;
    }

    /**
     * Transform the ApiSetup entity.
     *
     * @param \App\Entities\POSTerminalTransactionProduct $model
     *
     * @return array
     */
    public function transform(POSTerminalTransactionProduct $model)
    {
        if ($this->isProduct) {
            $data = [
                'bid' => $model->bid,
                'price' => $model->price,
                'description' => $model->description,
                'modifiers' => $this->modifier($model->bid)
            ];

            return $data;
        } else {
            $data = [
                'bid' => $model->bid,
                'price' => $model->price,
                'description' => $model->description,
            ];

            return $data;
        }
    }

    public function modifier($bid)
    {
        $modifier = app()->make(PosTerminalTransactionRepository::class)->AddOnModifier($bid);
        return $modifier = fractal($modifier, New CustomerEmailProductTransformer(false))->toArray()['data'];
    }
}
