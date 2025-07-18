<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISOrderTakingItemSetup extends Base
{
    use SoftDeletes;

    protected $table = 'cdis_order_taking_item_setup';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'branch_bid',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function detail()
    {
        return $this->hasMany(CDISOrderTakingItemSetupDetail::class, 'head_bid', 'bid');
    }

    public function branch()
    {
        return $this->hasMany(CDISBranch::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => 'cdis_order_taking_item_setup',
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }
}
