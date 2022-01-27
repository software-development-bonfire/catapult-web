<?php

namespace App\Entities;

class CDISTerminalTransaction extends Base
{
    protected $table = 'cdis_terminal_transaction';

    protected $fillable = [
        'terminal_bid',
        'transaction_id',
        'date',
        'amount',
        'transaction_type',
        'is_zread',
        'status',
        'type',
        'gross',
        'total_quantity',
        'total_free_items_amount',
        'total_local_tax_amount',
        'total_tax_amount',
        'total_discount_amount',
        'total_vat_deduct_amount',
        'total_vat_exempt_amount',
        'total_vatable_sales',
        'total_zero_rated_sales',
        'log_date',
        'order_number',
        'table_number',
        'guest_count',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'terminal_bid' => 'string',
        'transaction_id' => 'string',
    ];

    public function terminal()
    {
        return $this->belongsTo(CDISTerminal::class, 'terminal_bid', 'bid');
    }

    public function details()
    {
        return $this->hasMany(CDISTerminalTransactionDetail::class, 'transaction_head_bid', 'bid');
    }
}
