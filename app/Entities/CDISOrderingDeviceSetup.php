<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISOrderingDeviceSetup extends BaseModel
{
    protected $table = 'cdis_ordering_device_setup';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function orderingDeviceSetupBranch()
    {
        return $this->hasMany(CDISOrderingDeviceSetupBranch::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => $this->getTable(),
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }
}
