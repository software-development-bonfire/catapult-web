<?php

namespace App\Entities;
use App\Traits\BidObserverTrait;


class StoreOrder extends Base
{
    protected $table = 'store_order';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'terminal_bid',
        'terminal_id',
        'transaction_id',
        'order_number',
        'log_date',
        'transaction_from',
        'transaction_type',
        'table_id'
    ];

    protected $casts = [
        'bid' => 'string',
    ];

    public function detail()
    {
        return $this->hasMany(ItemAvailabilityDetail::class, 'head_bid', 'bid');
    }

}
