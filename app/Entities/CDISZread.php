<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CDISZread.
 *
 * @package namespace App\Entities;
 */
class CDISZread extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'cdis_zread';

    public $incrementing = false;
    
    protected $fillable = [
        'terminal_bid',
        "day_end_report_number",
        "administrator",
        "cashier",
        "log_date",
        "date_time",
        "terminal_number",
        "branch_code",
        "guest_count",
        "gross_sales_amount",
        "transaction_count",
        "mandated_discount_transaction_count",
        "total_regular_discount_count",
        "total_regular_discount_amount",
        "senior_transaction_count",
        "senior_discount_amount",
        "sc_vat_deduction_amount",
        "pwd_transaction_count",
        "pwd_transaction_amount",
        "pwd_vat_deduction",
        "diplomat_transaction_count",
        "diplomat_vat_deduction",
        "vatable_sales",
        "vat_amount",
        "vat_exempt_sales",
        "less_mandated_vat_and_discount",
        "net_of_vat_exempt",
        "zero_rated_sales",
        "non_vat_sales",
        "subtotal",
        "service_charge",
        "net_total",
        "beginning_or",
        "ending_or",
        "no_sales_transaction_count",
        "void_transactions_count",
        "void_transactions_amount",
        "void_items_count",
        "void_items_amount",
        "refunds_count",
        "refunds_amount",
        "total_tenders_count",
        "total_tenders_amount",
        "add_initial_cash_amount",
        "less_withdrawals_amount",
        "add_cash_returns_amount",
        "total_drawer_amount",
        "total_cash_breakdown_amount",
        "short_over_amount",
        "total_cashier_sales_amount",
        "beginning_balance",
        "ending_balance",
    ];

    protected $casts = [
        'bid' => 'string',
        'terminal_bid' => 'string',
    ];

    public function terminal()
    {
        return $this->belongsTo(CDISTerminal::class, 'terminal_bid', 'bid');
    }

    public function regularDiscount()
    {
        return $this->hasMany(CDISZreadRegularDiscount::class, 'head_bid', 'bid');
    }

    public function cashBreakdownDetail()
    {
        return $this->hasMany(CDISZreadCashBreakdownDetail::class, 'head_bid', 'bid');
    }

    public function tenderDetail()
    {
        return $this->hasMany(CDISZreadTenderDetail::class, 'head_bid', 'bid');
    }

    public function cashierSalesSummary()
    {
        return $this->hasMany(CDISZreadCashierSalesSummary::class, 'head_bid', 'bid');
    }
}
