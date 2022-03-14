<?php

namespace App\Entities;

class CDISDisplayPaymentMethodDeviceDisplay extends BaseModel
{
    protected $table = 'cdis_display_payment_method_device_display';

    protected $primaryKey = 'bid';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'ordering_device_setup_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'ordering_device_setup_bid' => 'string'
    ];

    public function displayPaymentMethod()
    {
        return $this->belongsTo(CDISDisplayPaymentMethod::class, 'head_bid', 'bid');
    }
}
