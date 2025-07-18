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
        'business_style',
        'contact_person',
        'contact_number',
        'payment_term_days',
        'branch_as_vendor_bid',
        'payment_term_settings_bid',
        'trade_type',
        'tin_no',
        'vat_principal',
        'email',
        'email_2',
        'country',
        'region_bid',
        'province_bid',
        'city_bid',
        'barangay_bid',
        'zip_code',
        'street_building_house_number',
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
            $syncDetails->group = $this->getTable() ?? 'cdis_vendor';
            $syncDetails->head_bid = $this->bid;
            $syncDetails->level = 1;
        }

        return $syncDetails;
    }

}
