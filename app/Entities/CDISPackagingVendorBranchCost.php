<?php

namespace App\Entities;

use App\Enums\CDIS\ApprovalStatus;
use App\Enums\CDIS\CostAndPriceChangePricingType;
use App\Enums\CDIS\CostAndPriceChangeType;
use Illuminate\Support\Facades\DB;

class CDISPackagingVendorBranchCost extends BaseModel
{
    protected $table = 'cdis_packaging_vendor_branch_cost';

    protected $fillable = [
        'bid',
        'packaging_vendor_bid',
        'product_branch_availability_bid',
        'cost',
        'price_to_branch',
        'price_to_branch_markup',
        'is_available',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'packaging_vendor_bid' => 'string',
        'product_branch_availability_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function getCurrentCostAttribute()
    {
        $model =
            DB::table("cdis_packaging_vendor_branch_cost")
                ->select([
                    DB::raw('cdis_product_branch_availability.bid as product_branch_availability_bid'),
                    DB::raw('cdis_packaging_vendor.bid as packaging_vendor_bid'),
                    DB::raw('cdis_packaging_vendor_branch_cost.bid as packaging_vendor_branch_cost_bid'),
                    DB::raw("IF(COUNT(CPDU.bid) > 0, COALESCE(GROUP_CONCAT(IFNULL(CPDU.new_value, 'NULL') ORDER BY CPDU.assessed_at DESC), cdis_packaging_vendor_branch_cost.cost), cdis_packaging_vendor_branch_cost.cost) AS cost"),
                ])
                ->rightJoin('cdis_product_branch_availability', 'cdis_product_branch_availability.bid', '=', 'cdis_packaging_vendor_branch_cost.product_branch_availability_bid')
                ->rightJoin('cdis_packaging_vendor', function ($join) {
                    $join->on('cdis_packaging_vendor.bid', '=', 'cdis_packaging_vendor_branch_cost.packaging_vendor_bid')
                        ->where('cdis_packaging_vendor.is_default', 1)
                        ->where('cdis_packaging_vendor.status', 1);
                })
                ->rightJoin('cdis_vendor', 'cdis_packaging_vendor.vendor_bid', '=', 'cdis_vendor.bid')
                ->rightJoin('cdis_product_uom_packaging', 'cdis_product_uom_packaging.bid', '=', 'cdis_packaging_vendor.product_uom_bid')
                ->leftJoin('cdis_branch', function ($join) {
                    $join->on('cdis_branch.bid', '=', 'product_branch_availability.branch_bid');

                    return $join;
                })
                ->leftJoinSub(
                    DB::table("cdis_cost_and_price_change_detail")
                        ->select([
                            'cdis_cost_and_price_change_detail.bid',
                            'cdis_cost_and_price_change_detail.product_uom_bid',
                            'cdis_cost_and_price_change.vendor_bid',
                            'cdis_cost_and_price_change_detail.new_value',
                            'cdis_cost_and_price_change.assessed_at',
                        ])
                        ->leftJoin(
                            'cdis_cost_and_price_change',
                            'cdis_cost_and_price_change.bid',
                            '=',
                            'cdis_cost_and_price_change_detail.head_bid')
                        ->where(function ($model) {
                            $model->where(function ($subModel) {
                                $subModel->where('cdis_cost_and_price_change.pricing_type', CostAndPriceChangePricingType::COST)
                                    ->where('cdis_cost_and_price_change.type', CostAndPriceChangeType::TIME_TRIGGER)
                                    ->whereRaw(DB::raw('NOW() BETWEEN cdis_cost_and_price_change.`effective_at` AND cdis_cost_and_price_change.`expires_at`'));

                                return $subModel;
                            })
                                ->orWhere(function ($subModel) {
                                    $subModel->where('cdis_cost_and_price_change.pricing_type', CostAndPriceChangePricingType::COST)
                                        ->where('cdis_cost_and_price_change.type', CostAndPriceChangeType::PERMANENT)
                                        ->whereRaw(DB::raw('NOW() >= cdis_cost_and_price_change.`effective_at`'));

                                    return $subModel;
                                });
                        })
                        ->whereNull('cdis_cost_and_price_change.deleted_at')
                        ->where('cdis_cost_and_price_change.status', ApprovalStatus::APPROVED)
                        ->orderBy('cdis_cost_and_price_change.assessed_at', 'DESC'),
                    'CPDU',
                    function ($join) {
                        $join->on('CPDU.product_uom_bid', 'cdis_product_uom_packaging.bid')
                            ->on('CPDU.vendor_bid', 'cdis_vendor.bid');
                    }
                )
                ->where('cdis_packaging_vendor_branch_cost.bid', '=', $this->bid)
                ->limit(1);

        $cost = null;

        if ($model->count() > 0) {
            $cost = $model->pluck('cost')[0];
            $cost =
                (strpos($cost, ',') !== false)
                    ? explode(',', $cost)[0]
                    : $cost;
        }

        return $cost;
    }
}
