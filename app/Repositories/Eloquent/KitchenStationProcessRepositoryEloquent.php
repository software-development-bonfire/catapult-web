<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISKitchenStationProcess;
use App\Repositories\Contracts\KitchenStationProcessRepository;
use Illuminate\Support\Collection;

class KitchenStationProcessRepositoryEloquent extends BaseEloquent implements KitchenStationProcessRepository
{
    public function model()
    {
        return CDISKitchenStationProcess::class;
    }
    /**
     * Get list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function list($filters = null)
    {
        $this->model = $this->model
            ->select([
                'bid',
                'code',
                'description',
                'kitchen_station_bid_1',
                'kitchen_station_bid_2',
                'kitchen_station_bid_3',
                'kitchen_station_bid_4',
                'status',
            ]);

        return $this->model->get();
    }
}
