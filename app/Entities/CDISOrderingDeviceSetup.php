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

    public function orderDeviceSetupBranch()
    {
        return $this->hasMany(CDISOrderingDeviceSetupBranch::class, 'head_bid', 'bid');
    }
}
