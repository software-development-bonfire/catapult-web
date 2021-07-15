<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class CDISProductBranchAvailability extends Model
{
    protected $table = 'cdis_product_branch_availability';

    protected $fillable = [
        'bid',
        'branch_bid',
        'product_uom_bid',
        'is_available',
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
}
