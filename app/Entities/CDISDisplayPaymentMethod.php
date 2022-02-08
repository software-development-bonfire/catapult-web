<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISDisplayPaymentMethod extends Base
{
    use SoftDeletes;

    protected $table = 'cdis_display_payment_method';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'branch_bid',
        'code',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string'
    ];

    public function displayPaymentMethodDeviceDisplay()
    {
        return $this->hasMany(CDISDisplayPaymentMethodDeviceDisplay::class, 'head_bid', 'bid');
    }

    public function displayPaymentMethodDetail()
    {
        return $this->hasMany(CDISDisplayPaymentMethodDetail::class, 'head_bid', 'bid');
    }
}
