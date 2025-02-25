<?php

namespace App\Repositories\Eloquent;

use App\Entities\CDISKitchenDevicePrinter;
use App\Entities\KitchenDisplay;
use App\Entities\KitchenDisplayDetail;
use App\Enums\KDS\DeviceType;
use App\Enums\KDS\OrderType;
use App\Repositories\Contracts\KitchenPrinterRepository;
use App\Traits\GenericHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KitchenPrinterRepositoryEloquent extends BaseEloquent implements KitchenPrinterRepository
{
    use GenericHelper;

    public function model()
    {
        return CDISKitchenDevicePrinter::class;
    }

    public function list($filters)
    {
        $this->model = $this->model
            ->select([
                'id',
                'bid',
                'code',
                'description',
                'device_printer',
                'printer_host',
                'local_printer',
                'status',
                'created_at',
                'updated_at',
            ])
            ->orderBy('bid', 'ASC');

        return $this->paginate(isset($filters['itemsPerPage']) ? $filters['itemsPerPage'] : 10);
    }

    /**
     * Get kitchen printers with item list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function getMenuPrinters($filters)
    {
        $productBids = collect($filters)->pluck('product_bid');

        $this->model =  DB::table('cdis_product_uom_packaging')
            ->select([
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
            ])
            ->leftJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.product_uom_packaging_bid', '=', 'cdis_product_uom_packaging.bid')
            ->leftJoin('cdis_kitchen_item_setup', 'cdis_kitchen_item_setup.bid', '=', 'cdis_kitchen_item_setup_detail.head_bid')
            ->leftJoin('cdis_kitchen_device_printer_branch', function ($join) {
                $join->on('cdis_kitchen_device_printer_branch.branch_bid', '=', 'cdis_kitchen_item_setup.branch_bid')
                    ->on('cdis_kitchen_device_printer_branch.kitchen_device_printer_bid', '=', 'cdis_kitchen_item_setup.device_type_bid');
            })
            ->leftJoin('cdis_kitchen_device_printer', 'cdis_kitchen_device_printer.bid', '=', 'cdis_kitchen_device_printer_branch.kitchen_device_printer_bid')
            ->where(function ($query) use ($productBids) {
                if (count($productBids) > 0) {
                    $query->whereIn("cdis_product_uom_packaging.bid", $productBids);
                }
            })
            ->where('cdis_kitchen_item_setup.device_type', DeviceType::KITCHEN_PRINTER)
            ->whereNull('cdis_kitchen_item_setup_detail.deleted_at')
            ->groupBy(['cdis_product_uom_packaging.bid']);

        return $this->model->get();
    }


    /**
     * Get kitchen printers with item list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function getProductIsPrintSticker($bid)
    {
        $this->model =  DB::table('cdis_product_uom_packaging')
            ->select([
                DB::raw('cdis_product_uom_packaging.barcode as `barcode`'),
                DB::raw('cdis_product_uom_packaging.description as `description`'),
                DB::raw('cdis_product_uom_packaging.long_description as `long_description`'),
                DB::raw('cdis_product_uom_packaging.is_print_sticker as `is_print_sticker`'),
            ])
            ->where('cdis_product_uom_packaging.bid', $bid)
            ->whereNull('cdis_product_uom_packaging.deleted_at')
            ->groupBy(['cdis_product_uom_packaging.bid']);

        return $this->model->first();
    }
}
