<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProduct extends Model
{
    use SoftDeletes;

    protected $table = 'cdis_product';

    protected $fillable = [
        'bid',
        'item_code',
        'category_bid',
        'brand_bid',
        'status',
        'is_sell_item',
        'is_inventory_item',
        'tax_code',
        'is_senior_item',
        'is_pwd_item',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'category_bid' => 'string',
        'item_code' => 'string',
        'brand_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
