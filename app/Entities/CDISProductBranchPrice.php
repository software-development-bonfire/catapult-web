<?php

namespace App\Entities;

use App\Enums\CDIS\ApprovalStatus;
use App\Enums\CDIS\CostAndPriceChangePricingType;
use App\Enums\CDIS\CostAndPriceChangeType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class CDISProductBranchPrice extends BaseModel
{
    protected $table = 'cdis_product_branch_price';

    protected $fillable = [
        'bid',
        'product_branch_availability_bid',
        'product_pricing_type_bid',
        'price',
        'markup',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_branch_availability_bid' => 'string',
        'product_pricing_type_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function productBranchAvailability()
    {
        return $this->belongsTo(CDISProductBranchAvailability::class, 'product_branch_availability_bid', 'bid');
    }

    public function productPricingType()
    {
        return $this->belongsTo(CDISProductPricingType::class, 'product_pricing_type_bid', 'bid');
    }

    public function productAddonDetail()
    {
        return $this->hasMany(CDISProductAddonDetail::class, 'product_branch_price_bid', 'bid');
    }

    public function productModifierDetail()
    {
        return $this->hasMany(CDISProductModifierDetail::class, 'product_branch_price_bid', 'bid');
    }

    public function getCurrentPriceAttribute()
    {
        $model =
            DB::table("cdis_product_branch_price")
                ->select(
                    DB::raw("IF(COUNT(CPDP.bid) > 0, COALESCE(GROUP_CONCAT(DISTINCT IFNULL(CPDP.new_value, 'NULL') ORDER BY CPDP.assessed_at DESC), cdis_product_branch_price.price), cdis_product_branch_price.price) AS selling_price")
                )
                ->leftJoin(
                    'cdis_product_branch_availability',
                    'cdis_product_branch_availability.bid',
                    '=',
                    'cdis_product_branch_price.product_branch_availability_bid')
                ->leftJoinSub(
                    DB::table("cdis_cost_and_price_change_detail")
                        ->select([
                            'cdis_cost_and_price_change_detail.bid',
                            'cdis_cost_and_price_change_detail.product_uom_bid',
                            'cdis_cost_and_price_change_detail.branch_bid',
                            'cdis_cost_and_price_change_detail.product_pricing_type_bid',
                            'cdis_cost_and_price_change_detail.new_value',
                            'cdis_cost_and_price_change.assessed_at'
                        ])
                        ->leftJoin(
                            'cdis_cost_and_price_change',
                            'cdis_cost_and_price_change.bid',
                            '=',
                            'cdis_cost_and_price_change_detail.head_bid')
                        ->where(function($model) {
                            $model->where(function($subModel) {
                                $subModel->where('cdis_cost_and_price_change.pricing_type', CostAndPriceChangePricingType::PRICE)
                                    ->where('cdis_cost_and_price_change.type', CostAndPriceChangeType::TIME_TRIGGER)
                                    ->whereRaw(DB::raw('NOW() BETWEEN cdis_cost_and_price_change.`effective_at` AND cdis_cost_and_price_change.`expires_at`'));

                                return $subModel;
                            })
                                ->orWhere(function($subModel) {
                                    $subModel->where('cdis_cost_and_price_change.pricing_type', CostAndPriceChangePricingType::PRICE)
                                        ->where('cdis_cost_and_price_change.type', CostAndPriceChangeType::PERMANENT)
                                        ->whereRaw(DB::raw('NOW() >= cdis_cost_and_price_change.`effective_at`'));

                                    return $subModel;
                                });
                        })
                        ->whereNull('cdis_cost_and_price_change.deleted_at')
                        ->where('cdis_cost_and_price_change.status', ApprovalStatus::APPROVED)
                        ->orderBy('cdis_cost_and_price_change.assessed_at', 'DESC'),
                    'CPDP',
                    function($join) {
                        $join->on('CPDP.product_uom_bid', 'cdis_product_branch_availability.product_uom_bid')
                            ->on('CPDP.branch_bid', 'cdis_product_branch_availability.branch_bid')
                            ->on('CPDP.product_pricing_type_bid', 'cdis_product_branch_price.product_pricing_type_bid');
                    }
                )
                ->where('cdis_product_branch_price.bid', '=', $this->bid);

        $price = null;

        if ($model->count() > 0) {
            $price = $model->pluck('selling_price')[0];
            $price =
                (strpos($price, ',') !== false)
                    ? explode(',', $price)[0]
                    : $price;

            $price = is_numeric($price) && (! is_null($price) || $price !== 'NULL')
                ? $price
                : null;
        }

        return $price;
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'group' => '',
            'head_bid' => '',
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $productBranchAvailabilityTableName = 'cdis_product_branch_availability';
        $uomPackagingTableName = 'cdis_product_uom_packaging';
        $productTableName = 'cdis_product';

        $productBid = null;
        $productUomBid = null;

        if ($this->productBranchAvailability !== null) {
            $productBranchAvailabilityTableName = $this->productBranchAvailability->getTable();
            $productUomBid = $this->productBranchAvailability->product_uom_bid;

            if ($this->productBranchAvailability->productUomPackaging !== null) {

                $uomPackagingTableName = $this->productBranchAvailability->productUomPackaging->getTable();
                $productBid = $this->productBranchAvailability->productUomPackaging->product_bid;

                if ($this->productBranchAvailability->productUomPackaging->product !== null) {
                    $productTableName = $this->productBranchAvailability->productUomPackaging->product->getTable();
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
            $syncDetails->head_bid = $productUomBid;
            $syncDetails->level = 3;
        } else if ($routeName == 'save_selling_data') {
            $syncDetails->group = null;
            $syncDetails->level = 1;
        }

        $syncDetails->reference_bid = json_encode([$this->product_branch_availability_bid, $this->product_pricing_type_bid]);
        $syncDetails->reference_table = json_encode([$productBranchAvailabilityTableName, 'cdis_product_pricing_type']);

        return $syncDetails;
    }
}
