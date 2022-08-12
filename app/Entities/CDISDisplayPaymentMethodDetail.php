<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISDisplayPaymentMethodDetail extends Base
{
    protected $table = 'cdis_display_payment_method_detail';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'payment_method_settings_bid',
        'name',
        'display_priority',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'payment_method_settings_bid' => 'string'
    ];

    public function displayPaymentMethod()
    {
        return $this->belongsTo(CDISDisplayPaymentMethod::class, 'head_bid', 'bid');
    }

    public function paymentMethodSettings()
    {
        return $this->belongsTo(CDISPaymentMethodSettings::class, 'payment_method_settings_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => $this->head_bid,
            'level' => 1,
            'reference_bid' => json_encode([$this->head_bid, $this->payment_method_settings_bid]),
            'reference_table' => json_encode([$this->displayPaymentMethod->getTable(), $this->paymentMethodSettings->getTable()])
        );
    }
}
