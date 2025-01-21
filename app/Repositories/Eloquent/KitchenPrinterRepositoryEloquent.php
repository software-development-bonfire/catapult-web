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
        $filters = (object) $filters;
        \Illuminate\Support\Facades\Log::alert(json_encode($filters));
        // Query for cdis_terminal_transaction_product
        $productQuery = DB::table('cdis_terminal_transaction_product')
            ->select([
                'bid',
                'transaction_detail_bid',
                'product_bid',
                'name',
                'description',
                'quantity',
                DB::raw('0 AS is_addon')
            ]);

        // Query for cdis_terminal_transaction_addon
        $addonQuery = DB::table('cdis_terminal_transaction_addon')
            ->select([
                'transaction_product_bid AS `bid`',
                DB::raw('NULL AS `transaction_detail_bid`'), // Add NULL since this column doesn't exist in the addon table
                'product_bid',
                'name',
                'description',
                'quantity',
                DB::raw('1 AS is_addon')
            ]);

        // Combine the two queries using unionAll
        $combinedQuery = $productQuery->unionAll($addonQuery);

        $this->model = $this->model
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
                DB::raw('combined.name as `name`'),
                DB::raw('combined.description as `description`'),
                DB::raw('combined.quantity as `quantity`'),
                DB::raw('combined.is_addon as `is_addon`'),
                DB::raw('cdis_terminal_transaction_detail.or_number as `or_number`'),
                DB::raw('cdis_terminal_transaction.transaction_id as `transaction_id`'),
            ])
            ->leftJoin('cdis_kitchen_device_printer_branch', 'cdis_kitchen_device_printer_branch.kitchen_device_printer_bid', '=', 'cdis_kitchen_device_printer.bid')
            //->leftJoin('cdis_kitchen_item_setup', 'cdis_kitchen_item_setup.branch_bid', '=', 'cdis_kitchen_device_printer_branch.branch_bid')
            ->leftJoin('cdis_kitchen_item_setup', function ($join) {
                $join->on('cdis_kitchen_item_setup.branch_bid', '=', 'cdis_kitchen_device_printer_branch.branch_bid')
                     ->on('cdis_kitchen_item_setup.device_type_bid', '=', 'cdis_kitchen_device_printer_branch.kitchen_device_printer_bid');
            })
            ->leftJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.head_bid', '=', 'cdis_kitchen_item_setup.bid')
            ->joinSub($combinedQuery, 'combined', function ($join) {
                $join->on('combined.product_bid', '=', 'cdis_kitchen_item_setup_detail.product_uom_packaging_bid');
            })
            ->leftJoin('cdis_terminal_transaction_detail', function ($join) {
                $join->on('cdis_terminal_transaction_detail.bid', '=', DB::raw('CASE WHEN combined.is_addon = 0 THEN combined.transaction_detail_bid ELSE NULL END'));
            })
            //->leftJoin('cdis_terminal_transaction_product', 'cdis_terminal_transaction_product.product_bid',  '=', 'cdis_kitchen_item_setup_detail.product_uom_packaging_bid')
            //->leftJoin('cdis_terminal_transaction_addon', 'cdis_terminal_transaction_addon.transaction_product_bid', '=', 'cdis_terminal_transaction_product.bid')
            //->leftJoin('cdis_terminal_transaction_detail', 'cdis_terminal_transaction_detail.bid', '=', 'combined.bid')
            ->leftJoin('cdis_terminal_transaction', 'cdis_terminal_transaction.bid', '=', 'cdis_terminal_transaction_detail.transaction_head_bid')
            ->where(function ($query) use ($filters) {
                if (! empty($filters['bid'])) {
                    $query->where("cdis_terminal_transaction.bid", $filters['bid']);
                }
            })
            ->whereNotNull('combined.name')
            ->groupBy(['cdis_kitchen_item_setup_detail.product_uom_packaging_bid']);

        \Illuminate\Support\Facades\Log::alert($this->getSqlWithBindings($this->model));

        return $this->model->get();
    }

    public function getMenuPrintersX($filters)
    {
        $filters = (object) $filters;
        \Illuminate\Support\Facades\Log::alert(json_encode($filters));
        // Query for cdis_terminal_transaction_product
        $productQuery = DB::table('cdis_terminal_transaction_product')
            ->select([
                'bid',
                'transaction_detail_bid',
                'product_bid',
                'name',
                'description',
                'quantity',
                DB::raw('0 AS is_addon')
            ]);

        // Query for cdis_terminal_transaction_addon
        $addonQuery = DB::table('cdis_terminal_transaction_addon')
            ->select([
                DB::raw('transaction_product_bid AS `bid`'),
                DB::raw('NULL AS `transaction_detail_bid`'), // Add NULL since this column doesn't exist in the addon table
                'product_bid',
                'name',
                'description',
                'quantity',
                DB::raw('1 AS is_addon')
            ]);

        // Combine the two queries using unionAll
        $combinedQuery = $productQuery->unionAll($addonQuery);

        $this->model =  DB::table('cdis_terminal_transaction')
            ->select([
                DB::raw('cdis_terminal_transaction.bid as `transaction_bid`'),
                DB::raw('cdis_terminal_transaction.transaction_id as `transaction_id`'),
                DB::raw('cdis_terminal_transaction_detail.or_number as `or_number`'),
                DB::raw('combined.name as `name`'),
                DB::raw('combined.description as `description`'),
                DB::raw('combined.quantity as `quantity`'),
                DB::raw('combined.is_addon as `is_addon`'),
                DB::raw('cdis_kitchen_device_printer.bid as `printer_bid`'),
                DB::raw('cdis_kitchen_device_printer.code as `printer_code`'),
                DB::raw('cdis_kitchen_device_printer.device_printer as `device_printer`'),
                DB::raw('cdis_kitchen_device_printer.printer_host as `printer_host`'),
                DB::raw('cdis_kitchen_device_printer.local_printer as `local_printer`'),
                DB::raw('cdis_kitchen_device_printer_branch.branch_bid as `branch_bid`'),
                DB::raw('cdis_kitchen_item_setup.device_type as `device_type`'),
                DB::raw('cdis_kitchen_item_setup.device_type_bid as `device_type_bid`'),
                DB::raw('cdis_kitchen_item_setup_detail.product_uom_packaging_bid as `product_uom_packaging_bid`'),
            ])
            ->leftJoin('cdis_terminal_transaction_detail', 'cdis_terminal_transaction_detail.transaction_head_bid', '=', 'cdis_terminal_transaction.bid')
            ->joinSub($combinedQuery, 'combined', function ($join) {
                $join->on(DB::raw('CASE WHEN combined.is_addon = 0 THEN combined.transaction_detail_bid ELSE NULL END'), '=', 'cdis_terminal_transaction_detail.bid');
            })
            ->leftJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.product_uom_packaging_bid', '=', 'combined.product_bid')
            ->leftJoin('cdis_kitchen_item_setup', 'cdis_kitchen_item_setup.bid', '=', 'cdis_kitchen_item_setup_detail.head_bid')
            ->leftJoin('cdis_kitchen_device_printer_branch', 'cdis_kitchen_device_printer_branch.branch_bid', '=', 'cdis_kitchen_item_setup.branch_bid')
            ->leftJoin('cdis_kitchen_device_printer', 'cdis_kitchen_device_printer.bid', '=', 'cdis_kitchen_device_printer_branch.kitchen_device_printer_bid')
            ->where(function ($query) use ($filters) {
                if (! empty($filters['bid'])) {
                    $query->where("cdis_terminal_transaction.bid", $filters['bid']);
                }
            })
            ->whereNotNull('combined.name')
            ->where('cdis_kitchen_item_setup.device_type', DeviceType::KITCHEN_PRINTER)
            ->groupBy([
                'cdis_terminal_transaction.bid',
                'cdis_terminal_transaction_detail.or_number',
                'combined.product_bid',
            ]);

        \Illuminate\Support\Facades\Log::alert($this->getSqlWithBindings($this->model));

        return $this->model->get();
    }
}
