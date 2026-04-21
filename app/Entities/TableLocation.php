<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class TableLocation extends Model
{
    protected $table = 'table_location';

    protected $fillable = [
        'name',
        'status',
    ];

    public function tables()
    {
        return $this->hasMany(DiningTable::class, 'location_id', 'id');
    }
}
