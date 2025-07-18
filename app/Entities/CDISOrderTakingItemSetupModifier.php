<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;


class CDISOrderTakingItemSetupModifier extends Base 
{
    use SoftDeletes;

    protected $table = 'cdis_order_taking_item_setup_modifier';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'order_taking_item_setup_detail_bid',
        'type',
        'name',
        'status',
        'mode',
        'mode_value',
        'is_allow_repetition_of_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'order_taking_item_setup_detail_bid' => 'string',
    ];

    public function setupDetail()
    {
        return $this->belongsTo(CDISOrderTakingItemSetupDetail::class, 'order_taking_item_setup_detail_bid', 'bid');
    }

    public function syncDetails()
    {
        return (object) array(
            'code' => null,
            'group' => $this->table,
            'head_bid' => null,
            'level' => 3,
            'reference_bid' => null,
            'reference_table' => null,
        );

    }
    
}
