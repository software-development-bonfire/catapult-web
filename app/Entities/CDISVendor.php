<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class CDISVendor extends BaseModel
{
    protected $table = 'cdis_vendor';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'description',
        'contact_person',
        'contact_number',
        'payment_term_days',
        'tin_no',
        'email',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function vendorBranch()
    {
        return $this->hasMany(CDISVendorBranch::class, 'vendor_bid','bid');
    }

    public function paymentTermsSettings()
    {
        return $this->hasOne(CDISPaymentTermSettings::class, 'bid','payment_term_settings_bid');
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => '',
            'group' => '',
            'head_bid' => '',
            'level' => 0,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if (
            $routeName == 'store_vendor'
            || $routeName == 'update_vendor'
        ) {
            $syncDetails->code = null;
            $syncDetails->group = $this->getTable();
            $syncDetails->head_bid = $this->bid;
            $syncDetails->level = 1;
        }

        return $syncDetails;
    }

}
