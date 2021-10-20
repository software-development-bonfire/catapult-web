<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISDiscountSettings extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cdis_discount_settings';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'method',
        'discount_type',
        'discount_amount',
        'receipt_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
    ];
}
