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

    public function orderingDeviceSetup()
    {
        return $this->belongsTo(CDISOrderingDeviceSetup::class, 'head_bid', 'bid');
    }

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => 'cdis_ordering_device_setup',
            'head_bid' => $this->orderingDeviceSetup !== null ? $this->orderingDeviceSetup->bid : null,
            'level' => 2,
            'reference_bid' => json_encode([$this->head_bid, $this->branch_bid]),
            'reference_table' => json_encode(['cdis_ordering_device_setup', 'cdis_branch'])
        );
    }
}
