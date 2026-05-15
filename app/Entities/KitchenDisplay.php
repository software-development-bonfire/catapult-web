<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenDisplay extends Base
{
    use SoftDeletes;

    protected $table = 'kitchen_display';

    protected $fillable = [
        'transaction_detail_bid',
        'transaction_date',
        'transaction_id',
        'terminal_bid',
        'terminal_number',
        'total_quantity',
        'completed_quantity',
        'completed_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
        'transaction_id' => 'string',
        'terminal_bid' => 'string',
        'total_quantity' => 'decimal:6',
        'completed_quantity' => 'decimal:6',
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
