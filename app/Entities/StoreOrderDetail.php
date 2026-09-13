<?php

namespace App\Entities;
use App\Traits\BidObserverTrait;


class StoreOrderDetail extends Base
{
    protected $table = 'store_order_detail';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'store_order_bid',
        'product_uom_bid',
        'menu_code',
        'name',
        'description',
        'long_description',
        'quantity',
        'usage_type',
        'parent_bid',
        'is_addon',
        'is_additional',
        'is_removed',
        'order_type_id',
        'special_request',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_uom_bid' => 'string',
    ];
}
