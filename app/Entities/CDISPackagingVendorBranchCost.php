<?php

namespace App\Entities;

use App\Enums\CDIS\ApprovalStatus;
use App\Enums\CDIS\CostAndPriceChangePricingType;
use App\Enums\CDIS\CostAndPriceChangeType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
        'discount_type',
        'discount_1',
        'discount_2',
        'discount_3',
        'discount_4',
        'adjustment',
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

    public function packagingVendor()
    {
        return $this->belongsTo(CDISPackagingVendor::class, 'packaging_vendor_bid', 'bid');
    }

    public function productBranchAvailability()
    {
        return $this->belongsTo(CDISProductBranchAvailability::class, 'product_branch_availability_bid', 'bid');
    }

    public function getCurrentCostAttribute()
    {
        $model =
            DB::table("cdis_packaging_vendor_branch_cost")
                ->select([
                    DB::raw('cdis_product_branch_availability.bid as product_branch_availability_bid'),
                    DB::raw('cdis_packaging_vendor.bid as packaging_vendor_bid'),
                    DB::raw('cdis_packaging_vendor_branch_cost.bid as packaging_vendor_branch_cost_bid'),
                    DB::raw("IF(COUNT(CPDU.bid) > 0, COALESCE(GROUP_CONCAT(DISTINCT IFNULL(CPDU.new_value, 'NULL') ORDER BY CPDU.assessed_at DESC), cdis_packaging_vendor_branch_cost.cost), cdis_packaging_vendor_branch_cost.cost) AS cost"),
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
                            'cdis_cost_and_price_change_detail.branch_bid',
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
                            ->on('CPDU.branch_bid', 'cdis_branch.bid')
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

            $cost = is_numeric($cost) && (! is_null($cost) || $cost !== 'NULL')
                ? $cost
                : null;
        }

        return $cost;
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $uomPackagingTableName = 'cdis_product_uom_packaging';
        $productTableName = 'cdis_product';

        $productBid = null;
        $productUomBid = null;

        if ($this->packagingVendor !== null) {
            $productUomBid = $this->packagingVendor->product_uom_bid;

            if ($this->packagingVendor->uomPackaging !== null) {

                $uomPackagingTableName = $this->packagingVendor->uomPackaging->getTable();
                $productBid = $this->packagingVendor->uomPackaging->product_bid;

                if ($this->packagingVendor->uomPackaging->product !== null) {
                    $productTableName = $this->packagingVendor->uomPackaging->product->getTable();
                }
            }
        }

        $routeName = Route::currentRouteName();

        if ($routeName == 'create_product') {
            $syncDetails->group = $productTableName;
            $syncDetails->head_bid = $productBid;
            $syncDetails->level = 4;
        } else if ($routeName == 'store_uom_packaging') {
            $syncDetails->group = $uomPackagingTableName;
            $syncDetails->head_bid =  $productUomBid;
            $syncDetails->level = 3;
        }

        $syncDetails->reference_bid = json_encode([$this->packaging_vendor_bid, $this->product_branch_availability_bid]);
        $syncDetails->reference_table = json_encode(['cdis_packaging_vendor', 'cdis_product_branch_availability']);

        return $syncDetails;
    }
}
