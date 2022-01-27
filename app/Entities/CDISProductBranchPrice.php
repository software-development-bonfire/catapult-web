<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductBranchPrice extends BaseModel
{
    use SoftDeletes;

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
}
