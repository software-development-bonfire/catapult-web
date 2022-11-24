<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISChargesSettings extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cdis_charges_settings';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'transaction_type',
        'charge_type',
        'charge_amount',
        'is_auto_apply',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'is_auto_apply' => 'boolean'
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function syncDetails()
    {
        $code = '';
        $group = $this->getTable() ?? 'charges_settings';
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
