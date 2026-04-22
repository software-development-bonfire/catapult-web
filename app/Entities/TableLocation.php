<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class TableLocation extends Model
{
    protected $table = 'table_location';

    protected $fillable = [
        'pos_id',
        'name',
        'location_name',
        'no_of_tables',
        'no_of_seats',
        'status',
    ];

    public function tables()
    {
        return $this->hasMany(DiningTable::class, 'location_id', 'id');
    }
}
