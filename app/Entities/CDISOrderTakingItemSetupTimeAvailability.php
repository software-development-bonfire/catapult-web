<?php

namespace App\Entities;

class CDISOrderTakingItemSetupTimeAvailability extends Base
{

    protected $table = 'order_taking_setup_time_availability';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'order_taking_item_setup_detail_bid',
        'time_from',
        'time_to',
    ];

    protected $casts = [
        'bid' => 'string',
        'order_taking_item_setup_detail_bid' => 'string',
    ];

    public function setupDetail()
    {
        return $this->belongsTo(CDISOrderTakingItemSetupDetail::class, 'order_taking_item_setup_detail_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => $this->table,
            'head_bid' => null,
            'level' => 3,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }
}
