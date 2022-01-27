<?php

namespace App\Entities;

class CDISPaymentMethodSettingsDetail extends BaseModel
{
    public $timestamps = false;

    protected $table = 'cdis_payment_method_settings_detail';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'field_name',
        'field_value',
        'is_required',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'is_required' => 'boolean'
    ];

    public function head()
    {
        return $this->belongsTo(CDISPaymentMethodSettings::class, 'head_bid', 'bid');
    }
}
