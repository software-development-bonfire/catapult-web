<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISBranch;
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
        $branchBid = CDISBranch::where('code', config('configuration.branch_code'))->whereNull('deleted_at')->value('bid');

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
            ])
            ->where('branch_bid', $branchBid);

        return $this->model->get();
    }
}
