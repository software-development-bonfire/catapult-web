<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISKitchenItemSetup;
use App\Repositories\Contracts\KitchenItemSetupRepository;
use App\Traits\GenericHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KitchenItemSetupRepositoryEloquent extends BaseEloquent implements KitchenItemSetupRepository
{
    use GenericHelper;

    public function model()
    {
        return CDISKitchenItemSetup::class;
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
                'branch_bid',
                'device_type_bid',
                'device_type',
                'status',
                'created_at',
                'created_by',
            ]);

        return $this->model->get();
    }

    public function details($filters)
    {
        $this->model = $this->model
            ->select([
                'cdis_kitchen_item_setup_detail.head_bid',
                'cdis_kitchen_item_setup_detail.kitchen_station_process_bid',
                'cdis_kitchen_item_setup_detail.product_uom_packaging_bid',
                DB::raw('cdis_kitchen_station_process.code as code'),
                DB::raw('cdis_kitchen_station_process.description as description'),
            ])
            ->leftJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.head_bid', '=', 'cdis_kitchen_item_setup.bid')
            ->leftJoin('cdis_kitchen_station_process', 'cdis_kitchen_station_process.bid', '=', 'cdis_kitchen_item_setup_detail.kitchen_station_process_bid')
            ->whereNull('cdis_kitchen_item_setup.deleted_at');


        if (isset($filters->transaction_product_bid) || $filters->transaction_product_bid != '') {
            $this->model->where('cdis_kitchen_item_setup_detail.product_uom_packaging_bid', $filters->transaction_product_bid);
        }
        $this->model->groupBy([
            'cdis_kitchen_item_setup_detail.bid'
        ]);
\Illuminate\Support\Facades\Log::alert($this->getSqlWithBindings($this->model));

        return $this->model->get();
    }
}
