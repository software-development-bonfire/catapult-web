<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISKitchenStation;
use App\Repositories\Contracts\KitchenStationRepository;
use Illuminate\Support\Collection;

class KitchenStationRepositoryEloquent extends BaseEloquent implements KitchenStationRepository
{
    public function model()
    {
        return CDISKitchenStation::class;
    }

    /**
     * Get station list
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
                'name',
                'queueing_group_type',
                'screen_prioritization',
                'status',
            ])
            ->orderBy('screen_prioritization', 'ASC');

        return $this->model->get();
    }
}
