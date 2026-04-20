<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenDisplay extends Base
{
    use SoftDeletes;

    protected $table = 'kitchen_display';

    protected $fillable = [
        'transaction_detail_bid',
        'transaction_id',
        'terminal_bid',
        'terminal_number',
        'system_mode',
        'order_id',
        'order_type_id',
        'order_type_name',
        'batch_number',
        'is_partial',
        'is_complete',
        'total_items',
        'completed_items',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
        'transaction_id' => 'string',
        'terminal_bid' => 'string',
        'order_id' => 'string',
        'batch_number' => 'integer',
        'is_partial' => 'boolean',
        'is_complete' => 'boolean',
        'total_items' => 'integer',
        'completed_items' => 'integer',
    ];

    public function details()
    {
        return $this->hasMany(KitchenDisplayDetail::class, 'head_bid', 'bid');
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
