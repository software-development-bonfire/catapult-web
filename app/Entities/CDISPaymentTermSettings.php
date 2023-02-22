<?php

namespace App\Entities;

class CDISPaymentTermSettings extends BaseModel
{
    protected $table = 'cdis_payment_term_settings';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function vendors()
    {
        return $this->belongsTo(CDISVendor::class,'bid', 'payment_term_settings_bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
        );
    }
}
