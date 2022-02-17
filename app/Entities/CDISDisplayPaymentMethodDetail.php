<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISDisplayPaymentMethodDetail extends Base
{
    use SoftDeletes;

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
}
