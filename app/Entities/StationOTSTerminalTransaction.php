<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class StationOTSTerminalTransaction extends Base
{
    use SoftDeletes;

    protected $table = 'station_ots_terminal_transactions';

    protected $fillable = [
        'branch_bid',
        'device_code',
        'terminal_bid',
        'transaction_id',
        'log_date',
        'or_number',
        'split_number',
        'is_first_transaction',
        'type',
        'device_type',
        'device_mode',
        'device_mode_label',
        'status',
        'gross_sales',
        'net_sales',
        'total_quantity',
        'total_free_items_amount',
        'total_local_tax_amount',
        'total_tax_amount',
        'total_discount_amount',
        'total_delivery_fee',
        'total_vat_deduct_amount',
        'total_vat_exempt_amount',
        'total_vatable_sales',
        'total_zero_rated_sales',
        'total_tender',
        'eligible_amount_to_earn_points',
        'guest_count',
        'service_charge',
        'order_number',
        'locator_number',
        'order_type',
        'order_schedule',
        'billing_type',
        'table_id',
        'table_number',
        'customer_type',
        'customer_bid',
        'customer_name',
        'customer_address',
        'cashier_bid',
        'cashier_name',
        'remarks',
        'created_at',
        'updated_at',
        'change',
        'payment',
        'payment_status',
        'is_reset',
        'receipt',
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'terminal_bid' => 'string',
        'transaction_bid' => 'string',
        'cashier_bid' => 'string',
        'customer_bid' => 'string',
    ];

    public function details()
    {
        return $this->hasMany(StationOTSTerminalTransactionProduct::class, 'terminal_transaction_bid', 'bid');
    }
}
