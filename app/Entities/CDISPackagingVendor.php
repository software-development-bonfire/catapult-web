<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class CDISPackagingVendor extends BaseModel
{
    protected $table = 'cdis_packaging_vendor';

    protected $fillable = [
        'bid',
        'vendor_bid',
        'product_uom_bid',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'vendor_bid' => 'string',
        'product_uom_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function vendor()
    {
        return $this->belongsTo(CDISVendor::class, 'vendor_bid', 'bid');
    }

    public function productUomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function uomPackaging()
    {
        return $this->belongsTo(CDISProductUomPackaging::class, 'product_uom_bid', 'bid');
    }

    public function packagingVendorBranchCost()
    {
        return $this->hasMany(CDISPackagingVendorBranchCost::class, 'packaging_vendor_bid', 'bid');
    }

    public function product()

    {
        return $this->uomPackaging()
            ->first()
            ->product()
            ->first();
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

        $routeName = Route::currentRouteName();

        if ($routeName == 'create_product') {
            $syncDetails->group = 'cdis_product';
            $syncDetails->head_bid = $this->productUomPackaging !== null ? $this->productUomPackaging->product_bid : null;
            $syncDetails->level = 3;
        } else if ($routeName == 'store_uom_packaging') {
            $syncDetails->group = 'cdis_product_uom_packaging';
            $syncDetails->head_bid = $this->product_uom_bid;
            $syncDetails->level = 2;
        }

        $syncDetails->reference_bid = json_encode([$this->product_uom_bid, $this->vendor_bid]);
        $syncDetails->reference_table = json_encode(['cdis_product_uom_packaging', 'cdis_vendor']);

        return $syncDetails;
    }
}
