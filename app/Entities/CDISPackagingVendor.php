<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function packagingVendorBranchCost()
    {
        return $this->hasMany(CDISPackagingVendorBranchCost::class, 'packaging_vendor_bid', 'bid');
    }
}
