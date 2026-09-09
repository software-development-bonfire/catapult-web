<?php

namespace App\Transformers\KDS\KitchenDisplay;

use App\Entities\KitchenDisplayDetail;
use App\Repositories\Contracts\CDISProductVariantRepository;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

/**
 * Class MenuListTransformer.
 *
 * @package namespace App\Transformers\KDS\KitchenDisplay;
 */
class MenuListTransformer extends TransformerAbstract
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

        $options = json_decode($model->variant_option);

        $filters = (object) [
            'product_variant_option_bid' => $options
        ];

        $variantAndOptions = app(CDISProductVariantRepository::class)->variantAndOptionList($filters);

        $variantNames = [];

        foreach ($variantAndOptions as $variantAndOption) {
            array_push($variantNames, $variantAndOption['option_name']);
        }

        $variantLabel = implode('/', $variantNames);

        return [
            'kitchen_display_bid' => (string) $model->kitchen_display_bid,
            'kitchen_display_detail_bid' => (string) $model->kitchen_display_detail_bid,
            'transaction_detail_bid' => (string) $model->transaction_detail_bid,
            'transaction_product_bid' => (string) $model->transaction_product_bid,
            'name' => $model->name,
            'quantity' => number_format($model->quantity, $decimal),
            'max_prep_time' => $model->max_prep_time,
            'max_waiting_time' => $model->max_waiting_time,
            'max_assembling_time' => $model->max_assembly_time,
            'max_serving_time' => $model->max_serving_time,
            'remaining_quantity' => number_format($model->remaining_quantity, $decimal),
            'remarks' => $model->remarks,
            'kitchen_station_bid' => (string) $model->kitchen_station_bid,
            'kitchen_station_process_bid' => (string) $model->kitchen_station_process_bid,
            'or_number' => (string) $model->or_number,
            'variant_label' => $variantLabel,
            'variant_names' => $variantNames,
            'status' => $model->status,
            'order_type' => $model->order_type,
            'created_at' => Carbon::parse($model->created_at)->format('Y-m-d h:i:s'),
            'updated_at' => Carbon::parse($model->updated_at)->format('Y-m-d h:i:s'),
            'addon' => $model->addon
        ];
    }
}
