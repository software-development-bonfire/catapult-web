<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISPackagingVendorBranchCost extends Model
{
    use SoftDeletes;

    protected $table = 'cdis_packaging_vendor_branch_cost';

    protected $fillable = [
        'bid',
        'packaging_vendor_bid',
        'product_branch_availability_bid',
        'cost',
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
