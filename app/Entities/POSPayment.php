<?php

namespace App\Entities;


class POSPayment extends Base
{
    protected $table = 'pos_payments';

    public $timestamps = false;

    protected $fillable = [
        'terminal_transaction_bid',
        'payment_method_bid',
        'title',
        'account_number',
        'amount',
        'status',
        'created_at',
        'log_date',
        'remarks'
    ];

    protected $casts = [
        'bid' => 'string',
        'payment_method_bid' => 'string',
    ];
}
