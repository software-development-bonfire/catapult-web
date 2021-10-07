<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISKitchenDevicePrinter extends Model
{
    use SoftDeletes;

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
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'is_printer_dispatch_copy' => 'bool',
    ];
}
