<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISOrderTakingItemSetupDetail extends Base
{
    use SoftDeletes;

    protected $table = 'cdis_order_taking_item_setup_detail';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'display_category_bid',
        'display_priority',
        'display_name',
        'is_available_whole_day',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'display_category_bid' => 'string',
        'is_available_whole_day' => 'boolean',
        'status' => 'boolean',
    ];

    public function head()
    {
        return $this->belongsTo(CDISOrderTakingItemSetup::class, 'head_bid', 'bid');
    }

    public function setupModifier()
    {
        return $this->hasMany(CDISOrderTakingItemSetupModifier::class, 'order_taking_item_setup_detail_bid', 'bid');
    }

    public function timeAvailability()
    {
        return $this->hasMany(CDISOrderTakingItemSetupTimeAvailability::class, 'order_taking_item_setup_detail_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => 'cdis_order_taking_item_setup_detail',
            'head_bid' => null,
            'level' => 2,
            'reference_bid' => null,
            'reference_table' => null,
        );
    }
}
