<?php

namespace App\Entities;

class CDISOrderingDeviceSetupBranch extends Base
{
    protected $table = 'cdis_ordering_device_setup_branch';

    protected $primaryKey = 'bid';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'branch_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'branch_bid' => 'string',
    ];

    public function orderDeviceSetup()
    {
        return $this->belongsTo(CDISOrderingDeviceSetup::class, 'head_bid', 'bid');
    }
}
