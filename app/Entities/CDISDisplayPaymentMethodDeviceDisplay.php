<?php

namespace App\Entities;

use Illuminate\Support\Facades\Route;

class CDISDisplayPaymentMethodDeviceDisplay extends BaseModel
{
    protected $table = 'cdis_display_payment_method_device_display';

    protected $primaryKey = 'bid';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'ordering_device_setup_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'ordering_device_setup_bid' => 'string'
    ];

    public function displayPaymentMethod()
    {
        return $this->belongsTo(CDISDisplayPaymentMethod::class, 'head_bid', 'bid');
    }

    public function orderingDeviceSetup()
    {
        return $this->belongsTo(CDISOrderingDeviceSetup::class, 'ordering_device_setup_bid', 'bid');
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

        if ($routeName == 'store_display_payment_method' || $routeName == 'update_display_payment_method') {
            $syncDetails->group = 'display_payment_method';
            $syncDetails->head_bid = $this->head_bid;
            $syncDetails->level = 2;
        }

        $syncDetails->reference_bid = json_encode([$this->head_bid, $this->ordering_device_setup_bid]);
        $syncDetails->reference_table = json_encode(['cdis_display_payment_method', 'cdis_ordering_device_setup']);

        return $syncDetails;
    }
}
