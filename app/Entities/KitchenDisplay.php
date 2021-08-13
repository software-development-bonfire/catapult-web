<?php

namespace App\Entities;

class KitchenDisplay extends Base
{
    protected $table = 'kitchen_display';

    protected $fillable = [
        'transaction_detail_bid',
        'completed_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
    ];
}
