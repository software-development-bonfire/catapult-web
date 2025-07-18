<?php

namespace App\Entities;

class CDISOrderTakingItemSetupDeviceSetup extends Base 
{

    protected $table = 'cdis_order_taking_item_setup_device_setup';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'order_taking_item_setup_detail_bid',
        'ordering_device_setup_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'order_taking_item_setup_detail_bid' => 'string',
        'ordering_device_setup_bid' => 'string',
    ];

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => $this->table,
            'head_bid' => null,
            'level' => 4,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }

}
