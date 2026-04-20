<?php

namespace App\Entities;

class KitchenDisplayMovementHistory extends Base
{
    protected $table = 'kitchen_display_movement_history';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $fillable = [
        'detail_bid',
        'from_station_index',
        'to_station_index',
        'quantity_moved',
        'movement_type',
        'status_before',
        'status_after',
    ];

    protected $casts = [
        'detail_bid' => 'string',
        'from_station_index' => 'integer',
        'to_station_index' => 'integer',
        'quantity_moved' => 'integer',
    ];

    public function detail()
    {
        return $this->belongsTo(KitchenDisplayDetail::class, 'detail_bid', 'bid');
    }
}
