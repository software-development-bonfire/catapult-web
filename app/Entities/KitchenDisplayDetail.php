<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenDisplayDetail extends Base
{
    use SoftDeletes;

    protected $table = 'kitchen_display_detail';

    protected $fillable = [
        'head_bid',
        'transaction_id',
        'transaction_product_bid',
        'product_uom_packaging_bid',
        'name',
        'remaining_quantity',
        'kitchen_station_bid',
        'kitchen_station_index',
        'status',
        'usage_type',
        'order_type_id',
        'order_type_name',
        'special_request',
        'is_addon',
        'addons',
        'terminal_number',

    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'transaction_product_bid' => 'string',
        'kitchen_station_bid' => 'string',
        'product_uom_packaging_bid' => 'string',
        'transaction_id' => 'string',
    ];

    public function head()
    {
        return $this->belongsTo(KitchenDisplay::class, 'head_bid','bid');
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
