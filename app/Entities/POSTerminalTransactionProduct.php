<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class POSTerminalTransactionProduct extends Base
{
    use SoftDeletes;

    protected $table = 'pos_terminal_transaction_products';

    protected $fillable = [
        'bid',
        'cart_bid',
        'terminal_transaction_bid',
        'usage_type',
        'product_bid',
        'name',
        'description',
        'long_description',
        'menu_code',
        'category_bid',
        'quantity',
        'tax_percentage',
        'order_type_id',
        'order_type_name',
        'is_free',
        'tax_code',
        'original_price',
        'price',
        'individual_total_amount',
        'individual_total_discount',
        'entire_discount',
        'entire_amount',
        'vatable_sales',
        'zero_rated_sales',
        'tax',
        'vat_deduct',
        'vat_exempt',
        'remarks',
        'supervisor_bid',
        'supervisor_name',
        'created_at',
        'parent_id',
        'add_on',
        'take_home',
        'sub_total',
        'gross_total',
        'net_total',
        'transaction_date',
        'log_date',
        'cashier_bid',
        'cashier_name',
        'discount_bid',
        'discount_value',
        'is_reset',
    ];

    protected $casts = [
        'bid' => 'string',
        'cart_bid' => 'string',
        'terminal_transaction_bid' => 'string',
        'product_bid' => 'string',
        'category_bid' => 'string',
        'supervisor_bid' => 'string',
        'cashier_bid' => 'string',
    ];

    public function head()
    {
        return $this->belongsTo(POSTerminalTransaction::class, 'terminal_transaction_bid', 'transaction_id');
    }
}
