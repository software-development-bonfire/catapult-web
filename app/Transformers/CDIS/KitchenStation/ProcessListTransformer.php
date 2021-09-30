<?php

namespace App\Transformers\CDIS\KitchenStation;

use App\Entities\CDISKitchenStationProcess;
use League\Fractal\TransformerAbstract;

/**
 * Class ProcessListTransformer.
 *
 * @package namespace App\Transformers\CDIS\KitchenStation;
 */
class ProcessListTransformer extends TransformerAbstract
{
    /**
     * Transform the ApiSetup entity.
     *
     * @param CDISKitchenStationProcess $model
     *
     * @return array
     */
    public function transform(CDISKitchenStationProcess $model)
    {
        return [
            'bid' => (string) $model->bid,
            'code' => $model->code,
            'description' => $model->description,
            'station' => array_filter([
                (string) $model->kitchen_station_bid_1,
                (string) $model->kitchen_station_bid_2,
                (string) $model->kitchen_station_bid_3,
                (string) $model->kitchen_station_bid_4,
            ]),
            'status' => $model->status
        ];
    }
}
