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
        'open_cash_drawer',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'open_cash_drawer' => 'boolean'
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
}
