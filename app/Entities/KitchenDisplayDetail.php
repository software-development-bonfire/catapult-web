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
        'product_uom_packaging_bid',
        'transaction_id',
        'remaining_quantity',
        'kitchen_station_bid',
        'status',
        'order_type_id',
        'order_type_name',
        'usage_type',
        'special_request',
        'addons',
        'is_addon',
        'name',
        'terminal_number',
        'started_at',
        'end_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'transaction_product_bid' => 'string',
        'kitchen_station_bid' => 'string',
        'product_uom_packaging_bid' => 'string',
        'transaction_id' => 'string',
        'remaining_quantity' => 'decimal:6',
        'is_addon' => 'boolean',
        'started_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function head()
    {
        return $this->belongsTo(KitchenDisplay::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
        );
    }
}
