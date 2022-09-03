<?php

namespace App\Entities;

use Illuminate\Support\Facades\Route;

class CDISVendorBranch extends BaseModel
{
    protected $table = 'cdis_vendor_branch';

    protected $primaryKey = 'bid';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'vendor_bid' => 'string'
    ];

    protected $fillable = [
        'bid',
        'branch_bid',
        'vendor_bid'
    ];

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid','bid');
    }

    public function vendor()
    {
        return $this->belongsTo(CDISVendor::class, 'vendor_bid','bid');
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => '',
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if (
            $routeName == 'store_vendor'
            || $routeName == 'update_vendor'
        ) {
            $referenceTable = $this->vendor !== null ? $this->vendor->getTable() : null;

            $syncDetails->code = null;
            $syncDetails->group = $referenceTable;
            $syncDetails->head_bid = $this->vendor_bid;
            $syncDetails->level = 2;
        }

        $syncDetails->reference_bid = $this->vendor_bid;
        $syncDetails->reference_table = $this->getTable() ?? 'cdis_vendor_branch';

        return $syncDetails;
    }
}
