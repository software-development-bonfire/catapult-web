<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class CDISKitchenDevicePrinterBranch extends Model
{
    protected $table = 'cdis_kitchen_device_printer_branch';

    protected $primaryKey = 'bid';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'kitchen_device_printer_bid',
        'branch_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'kitchen_device_printer_bid' => 'string',
        'branch_bid' => 'string',
    ];
}
