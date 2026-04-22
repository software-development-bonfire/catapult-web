<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class DiningTable extends Model
{
    protected $table = 'table';

    protected $fillable = [
        'pos_table_id',
        'location_id',
        'transaction_no',
        'table_ref',
        'name',
        'seat_number',
        'is_available',
        'status',
        'availability',
        'date',
        'total',
        'number_of_guest',
        'shape',
        'positionX',
        'position_y',
        'height',
        'width',
        'angle',
        'is_placed',
        'no_of_items',
    ];

    public function location()
    {
        return $this->belongsTo(TableLocation::class, 'location_id', 'id');
    }
}
