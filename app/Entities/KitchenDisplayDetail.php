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
        'original_quantity',
        'kitchen_station_bid',
        'kitchen_station_index',
        'current_station_index',
        'station_sequence',
        'current_position_in_sequence',
        'order_sequence',
        'batch_number',
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
        'current_station_index' => 'integer',
        'current_position_in_sequence' => 'integer',
        'order_sequence' => 'integer',
        'batch_number' => 'integer',
        'original_quantity' => 'integer',
        'remaining_quantity' => 'integer',
        'is_addon' => 'boolean',
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
