<?php

namespace App\Entities;

class CDISPaymentTermSettings extends BaseModel
{
    protected $table = 'cdis_payment_term_settings';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
