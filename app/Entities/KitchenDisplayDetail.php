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
        'transaction_type',
        'quantity',
        'remaining_quantity',
        'prepared_quantity',
        'bumped_quantity',
        'assembled_quantity',
        'released_quantity',
        'kitchen_station_bid',
        'status',
        'action_type',
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
        'max_waiting_time',
        'max_preparation_time',
        'max_assembly_time',
        'max_serving_time',
        'sent_at',
        'prepared_at',
        'bumped_at',
        'assembled_at',
        'served_at',
        'recall_reason',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'transaction_product_bid' => 'string',
        'kitchen_station_bid' => 'string',
        'product_uom_packaging_bid' => 'string',
        'transaction_id' => 'string',
        'quantity' => 'decimal:6',
        'remaining_quantity' => 'decimal:6',
        'prepared_quantity' => 'decimal:6',
        'bumped_quantity' => 'decimal:6',
        'released_quantity' => 'decimal:6',
        'action_type' => 'integer',
        'is_addon' => 'boolean',
        'started_at' => 'datetime',
        'end_at' => 'datetime',
        'sent_at' => 'datetime',
        'prepared_at' => 'datetime',
        'bumped_at' => 'datetime',
        'served_at' => 'datetime',
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
