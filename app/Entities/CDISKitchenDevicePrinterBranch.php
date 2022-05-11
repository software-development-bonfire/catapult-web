<?php

namespace App\Entities;

class CDISKitchenDevicePrinterBranch extends BaseModel
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

    public function kitchenDevicePrinter()
    {
        return $this->belongsTo(CDISKitchenDevicePrinter::class, 'kitchen_device_printer_bid', 'bid');
    }

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid', 'bid');
    }
}
