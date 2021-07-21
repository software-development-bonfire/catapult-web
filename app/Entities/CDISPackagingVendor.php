<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISPackagingVendor extends Model
{
    use SoftDeletes;

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
}
