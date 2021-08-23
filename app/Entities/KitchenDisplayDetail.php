<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenDisplayDetail extends Base
{
    use SoftDeletes;

    protected $table = 'kitchen_display_detail';

    protected $fillable = [
        'head_bid',
        'transaction_product_bid',
        'remaining_quantity',
        'kitchen_station_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'transaction_product_bid' => 'string',
        'kitchen_station_bid' => 'string',
    ];
}
