<?php

namespace App\Entities;

use Illuminate\Support\Facades\Route;

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

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if ($routeName == 'store_kitchen_device_printer_setup' || $routeName == 'update_kitchen_device_printer_setup') {
            $syncDetails->group = 'cdis_kitchen_device_printer';
            $syncDetails->head_bid =  $this->head !== null ? $this->head->bid : null;
            $syncDetails->level = 2;
        }

        $syncDetails->reference_bid = json_encode([$this->kitchen_device_printer_bid, $this->branch_bid]);
        $syncDetails->reference_table = json_encode(['cdis_kitchen_device_printer', 'cdis_branch']);

        return $syncDetails;
    }
}
