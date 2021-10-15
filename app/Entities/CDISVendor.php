<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISVendor extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cdis_vendor';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'description',
        'contact_person',
        'contact_number',
        'payment_term_days',
        'tin_no',
        'email',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];
}
