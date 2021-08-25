<?php

namespace App\Repositories\Eloquent;

use App\Entities\KitchenDisplayDetail;
use App\Repositories\Contracts\KitchenDisplayRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KitchenDisplayRepositoryEloquent extends BaseEloquent implements KitchenDisplayRepository
{
    public function model()
    {
        return KitchenDisplayDetail::class;
    }

    /**
     * Get menu list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function getMenuList($filters = null)
    {
        $this->model = $this->model
            ->select([
                DB::raw('kitchen_display.bid as kitchen_display_bid'),
                DB::raw('kitchen_display_detail.bid as kitchen_display_detail_bid'),
                'kitchen_display.transaction_detail_bid',
                'kitchen_display_detail.transaction_product_bid',
                'cdis_terminal_transaction_product.name',
                'cdis_terminal_transaction_product.quantity',
                'kitchen_display_detail.remaining_quantity',
                'cdis_terminal_transaction_product.remarks',
                'kitchen_display_detail.kitchen_station_bid',
                'cdis_kitchen_item_setup_detail.kitchen_station_process_bid',
                'cdis_terminal_transaction_detail.or_number',
                'kitchen_display_detail.status',
                'kitchen_display_detail.created_at',
                'kitchen_display_detail.updated_at',
            ])
            ->rightJoin('kitchen_display', 'kitchen_display.bid', '=', 'kitchen_display_detail.head_bid')
            ->rightJoin('cdis_terminal_transaction_product', 'cdis_terminal_transaction_product.bid', '=', 'kitchen_display_detail.transaction_product_bid')
            ->rightJoin('cdis_terminal_transaction_detail', 'cdis_terminal_transaction_detail.bid', '=', 'kitchen_display.transaction_detail_bid')
            ->rightJoin('cdis_kitchen_item_setup_detail', 'cdis_kitchen_item_setup_detail.product_uom_packaging_bid', '=', 'cdis_terminal_transaction_product.product_bid')
            ->whereNull('kitchen_display.completed_at')
            ->groupBy(['kitchen_display_detail.bid']);

        return $this->model->get();
    }

    /**
     * Get addon list
     *
     * @param Object $filters
     * @return Collection $result.
     */
    public function getAddonList($filters = null)
    {
        $this->model = $this->model
            ->select([
                'cdis_terminal_transaction_addon.product_bid',
                'cdis_terminal_transaction_addon.name',
                'cdis_terminal_transaction_addon.quantity',
            ])
            ->rightJoin('kitchen_display', 'kitchen_display.bid', '=', 'kitchen_display_detail.head_bid')
            ->leftJoin('cdis_terminal_transaction_addon', 'cdis_terminal_transaction_addon.transaction_product_bid', '=', 'kitchen_display_detail.transaction_product_bid');

        if (isset($filters->transaction_product_bid) || $filters->transaction_product_bid != '') {
            $this->model->where('cdis_terminal_transaction_addon.transaction_product_bid', $filters->transaction_product_bid);
        }

        return $this->model->get();
    }
}
