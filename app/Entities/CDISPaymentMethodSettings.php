<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISPaymentMethodSettings extends BaseModel
{
    protected $table = 'cdis_payment_method_settings';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'validation_type',
        'receipt_count',
        'is_default',
        'get_exact_amount',
        'open_cash_drawer',
        'payment_charge_type_bid',
        'payment_tender_type_bid',
        'payment_transaction_type_bid',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'is_default' => 'boolean',
        'get_exact_amount' => 'boolean',
        'open_cash_drawer' => 'boolean',
        'payment_charge_type_bid' => 'string',
        'payment_tender_type_bid' => 'string',
        'payment_transaction_type_bid' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function paymentMethodSettingsDetail()
    {
        return $this->hasMany(CDISPaymentMethodSettingsDetail::class, 'head_bid', 'bid');
    }

    public function chargeType()
    {
        return $this->hasOne(CDISPaymentChargeType::class, 'bid', 'payment_charge_type_bid');
    }

    public function tenderType()
    {
        return $this->hasOne(CDISPaymentTenderType::class, 'bid', 'payment_tender_type_bid');
    }

    public function transactionType()
    {
        return $this->hasOne(CDISPaymentTransactionType::class, 'bid', 'payment_transaction_type_bid');
    }

    public function syncDetails()
    {
        $code = '';
        $group = $this->getTable() ?? 'cdis_payment_method_settings';
        $headBid = $this->bid;
        $level = 1;

        return (object) array(
            'code' => $code,
            'group' => $group,
            'head_bid' => $headBid,
            'level' => $level,
        );
    }
}
