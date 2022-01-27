<?php

namespace App\Entities;

class CDISVendorBranch extends BaseModel
{
    protected $table = 'cdis_vendor_branch';

    protected $primaryKey = 'bid';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'vendor_bid' => 'string'
    ];

    protected $fillable = [
        'bid',
        'branch_bid',
        'vendor_bid'
    ];
}
