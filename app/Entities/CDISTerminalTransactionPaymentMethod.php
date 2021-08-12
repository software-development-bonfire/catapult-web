<?php

namespace App\Entities;

class CDISTerminalTransactionPaymentMethod extends Base
{
    protected $table = 'cdis_terminal_transaction_payment_method';

    protected $fillable = [
        'transaction_detail_bid',
        'payment_method_bid',
        'title',
        'total',
        'account_number',
        'remarks',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
        'payment_method_bid' => 'string',
    ];
}
