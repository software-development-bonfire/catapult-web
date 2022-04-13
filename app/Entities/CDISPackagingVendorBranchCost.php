<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

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
}
