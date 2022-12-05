<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class KitchenDisplay extends Base
{
    use SoftDeletes;

    protected $table = 'kitchen_display';

    protected $fillable = [
        'transaction_detail_bid',
        'completed_at',
    ];

    protected $casts = [
        'bid' => 'string',
        'transaction_detail_bid' => 'string',
    ];

    public function details()
    {
        return $this->hasMany(KitchenDisplayDetail::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
        );
    }
}
