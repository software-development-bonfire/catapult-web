<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISKitchenItemSetup;
use App\Enums\API\DeviceType as APIDeviceType;
use App\Enums\KDS\DeviceType;
use App\Enums\Status;
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
            ->whereNull('cdis_kitchen_item_setup.deleted_at')
            ->where('cdis_kitchen_item_setup.device_type', DeviceType::KITCHEN_DISPLAY)
            ->where('cdis_kitchen_item_setup.status', Status::ACTIVE)
            ->orderBy('cdis_kitchen_item_setup.created_at', 'DESC');


        if (! empty($filters->transaction_product_bid)) {
            $this->model->where('cdis_kitchen_item_setup_detail.product_uom_packaging_bid', $filters->transaction_product_bid);
            $this->model->groupBy([
                'cdis_kitchen_item_setup_detail.product_uom_packaging_bid',
                //'cdis_kitchen_item_setup_detail.kitchen_station_process_bid'
            ]);
        } else {
            $this->model->groupBy([
                'cdis_kitchen_item_setup_detail.bid'
            ]);
        }
        \Illuminate\Support\Facades\Log::alert($this->getSqlWithBindings($this->model));
        return $this->model->get();
    }

    public function getInitialKitchenStation($bid)
    {
        /*
        $this->model = $this->model
            ->select([
                'cdis_kitchen_item_setup_detail.head_bid',
                'cdis_kitchen_item_setup_detail.kitchen_station_process_bid',
                'cdis_kitchen_item_setup_detail.product_uom_packaging_bid',
                DB::raw('cdis_kitchen_station_process.code as station_code'),
                DB::raw('cdis_kitchen_station_process.description as station_name'),
                DB::raw('device_settings.device_code as device_code'),
                DB::raw('device_settings.device_uid as device_uid'),
                DB::raw('device_settings.name as device_name'),
            ])
            ->leftJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.head_bid', '=', 'cdis_kitchen_item_setup.bid')
            ->leftJoin('cdis_kitchen_station_process', 'cdis_kitchen_station_process.bid', '=', 'cdis_kitchen_item_setup_detail.kitchen_station_process_bid')
            ->leftJoin('device_settings', 'device_settings.kitchen_station_bid', '=', 'cdis_kitchen_station_process.kitchen_station_bid_1')
            ->whereNull('cdis_kitchen_item_setup.deleted_at')
            ->where('cdis_kitchen_item_setup.device_type', DeviceType::KITCHEN_DISPLAY)
            ->where('cdis_kitchen_item_setup.status', Status::ACTIVE)
            ->where('device_settings.device_type', APIDeviceType::KDS)
            ->where('cdis_kitchen_item_setup_detail.product_uom_packaging_bid', $bid)
            ->orderBy('cdis_kitchen_item_setup.created_at', 'DESC');

        return $this->model->first();
        */
        return $this->getKitchenStation($bid, 1);
    }


    public function getKitchenStation($bid, $index)
    {
        $this->model = $this->model
            ->select([
                'cdis_kitchen_item_setup_detail.head_bid',
                'cdis_kitchen_item_setup_detail.kitchen_station_process_bid',
                'cdis_kitchen_item_setup_detail.product_uom_packaging_bid',
                DB::raw('cdis_kitchen_station.name as station_name'),
                DB::raw('cdis_kitchen_station.order_type as order_type'),
                DB::raw('cdis_kitchen_station_process.bid as station_bid'),
                DB::raw('cdis_kitchen_station_process.code as station_code'),
                DB::raw('cdis_kitchen_station_process.description as station_process_name'),
                DB::raw('cdis_kitchen_station_process.kitchen_station_bid_' . $index.' as station_bid_'.$index),
                DB::raw('device_settings.device_code as device_code'),
                DB::raw('device_settings.device_uid as device_uid'),
                DB::raw('device_settings.name as device_name'),
            ])
            ->leftJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.head_bid', '=', 'cdis_kitchen_item_setup.bid')
            ->leftJoin('cdis_kitchen_station_process', 'cdis_kitchen_station_process.bid', '=', 'cdis_kitchen_item_setup_detail.kitchen_station_process_bid')
            ->leftJoin('cdis_kitchen_station', 'cdis_kitchen_station.bid', '=', 'cdis_kitchen_station_process.kitchen_station_bid_' . $index)
            ->leftJoin('device_settings', 'device_settings.kitchen_station_bid', '=', 'cdis_kitchen_station_process.kitchen_station_bid_' . $index)
            ->whereNull('cdis_kitchen_item_setup.deleted_at')
            ->where('cdis_kitchen_item_setup.device_type', DeviceType::KITCHEN_DISPLAY)
            ->where('cdis_kitchen_item_setup.status', Status::ACTIVE)
            ->where('device_settings.device_type', APIDeviceType::KDS)
            ->whereNull('device_settings.deleted_at')
            ->where('cdis_kitchen_item_setup_detail.product_uom_packaging_bid', $bid)
            ->orderBy('cdis_kitchen_item_setup.created_at', 'DESC');

          
        return $this->model->first();
    }
}
