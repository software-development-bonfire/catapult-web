<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISKitchenStation;
use App\Enums\KDS\DeviceType;
use App\Repositories\Contracts\KitchenStationRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function getProductKitchenStation($bid)
    {
        $selectColumns = [
            DB::raw('cdis_kitchen_device_printer.bid as `bid`'),
            DB::raw('cdis_kitchen_device_printer.code as `code`'),
            DB::raw('cdis_kitchen_device_printer.device_printer as `device_printer`'),
            DB::raw('cdis_kitchen_device_printer.printer_host as `printer_host`'),
            DB::raw('cdis_kitchen_device_printer.local_printer as `local_printer`'),
            
            DB::raw('cdis_kitchen_device_printer_branch.branch_bid as `branch_bid`'),
            DB::raw('cdis_kitchen_item_setup.device_type as `device_type`'),
            DB::raw('cdis_kitchen_item_setup.device_type_bid as `device_type_bid`'),
            DB::raw('cdis_kitchen_item_setup_detail.product_uom_packaging_bid as `product_uom_packaging_bid`'),
            DB::raw('cdis_product_uom_packaging.barcode as `barcode`'),
            DB::raw('cdis_product_uom_packaging.description as `description`'),
            DB::raw('cdis_product_uom_packaging.long_description as `long_description`'),
            DB::raw('cdis_product_uom_packaging.is_print_sticker as `is_print_sticker`'),
        ];

        $this->model = DB::table('cdis_product_uom_packaging')
            ->select($selectColumns)
            ->leftJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.product_uom_packaging_bid', '=', 'cdis_product_uom_packaging.bid')
            ->leftJoin('cdis_kitchen_item_setup', 'cdis_kitchen_item_setup.bid', '=', 'cdis_kitchen_item_setup_detail.head_bid')
            ->leftJoin('cdis_kitchen_device_printer_branch', function ($join) {
                $join->on('cdis_kitchen_device_printer_branch.branch_bid', '=', 'cdis_kitchen_item_setup.branch_bid')
                    ->on('cdis_kitchen_device_printer_branch.kitchen_device_printer_bid', '=', 'cdis_kitchen_item_setup.device_type_bid');
            })
            ->leftJoin('cdis_kitchen_device_printer', 'cdis_kitchen_device_printer.bid', '=', 'cdis_kitchen_device_printer_branch.kitchen_device_printer_bid')
            ->where('cdis_product_uom_packaging.bid', $bid)
            ->where('cdis_kitchen_item_setup.device_type', DeviceType::KITCHEN_DISPLAY)
            ->whereNull('cdis_kitchen_item_setup_detail.deleted_at')
            ->groupBy(['cdis_product_uom_packaging.bid']);

        return $this->model->first();
    }
}
