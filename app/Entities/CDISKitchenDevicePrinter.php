<?php

namespace App\Entities;

use App\Enums\KDS\DeviceType;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISKitchenDevicePrinter extends BaseModel
{
    protected $table = 'cdis_kitchen_device_printer';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'device_printer',
        'printer_host',
        'is_printer_dispatch_copy',
        'status',
        'local_printer',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'is_printer_dispatch_copy' => 'bool',
    ];

    public function kitchenItemSetup()
    {
        return $this->hasMany(CDISKitchenItemSetup::class, 'device_type_bid', 'bid')->where('device_type', DeviceType::KITCHEN_PRINTER);
    }

    public function kitchenDevicePrinterBranch()
    {
        return $this->hasMany(CDISKitchenDevicePrinterBranch::class, 'device_type_bid', 'bid');
    }
    public function branches()
    {
        return $this->hasMany(CDISKitchenDevicePrinterBranch::class, 'kitchen_device_printer_bid','bid');
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
