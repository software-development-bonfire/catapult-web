<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISDiscountSettings extends BaseModel
{
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
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
    ];

    public function syncDetails()
    {
        $code = '';
        $group = null;
        $headBid = $this->bid;
        $level = 1;

        return (object) array(
            'code' => $code,
            'group' => $group,
            'head_bid' => $headBid,
            'level' => $level,
        );
    }
}
