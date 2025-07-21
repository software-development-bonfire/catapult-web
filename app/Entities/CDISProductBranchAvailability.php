<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class CDISProductBranchAvailability extends BaseModel
{
    protected $table = 'cdis_product_branch_availability';

    protected $fillable = [
        'bid',
        'branch_bid',
        'product_uom_bid',
        'is_available',
        'ecomm_stock_availability',
        'min_stock',
        'max_stock',
        'created_by',
        'updated_by',
        'deleted_at'
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'product_uom_bid' => 'string',
        'is_available' => 'boolean',
    ];

    public function productBranchPrice()
    {
        return $this->hasMany(CDISProductBranchPrice::class, 'product_branch_availability_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
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
        
        if ($this->productUomPackaging !== null) {

            $uomPackagingTableName = $this->productUomPackaging->getTable();
            $productBid = $this->productUomPackaging->product_bid;

            if ($this->productUomPackaging->product !== null) {
                $productTableName = $this->productUomPackaging->product->getTable();
            }
        }

        $routeName = Route::currentRouteName();

        if ($routeName == 'create_product') {
            $syncDetails->group = $productTableName;
            $syncDetails->head_bid = $productBid;
            $syncDetails->level = 3;
        } else if ($routeName == 'store_uom_packaging') {
            $syncDetails->group = $uomPackagingTableName;
            $syncDetails->head_bid = $this->product_uom_bid;
            $syncDetails->level = 2;
        }

        $syncDetails->reference_bid = json_encode([$this->branch_bid, $this->product_uom_bid]);
        $syncDetails->reference_table = json_encode(['cdis_branch', 'cdis_product_uom_packaging']);

        return $syncDetails;
    }
}
