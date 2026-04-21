<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class DiningTable extends Model
{
    protected $table = 'table';

    protected $fillable = [
        'location_id',
        'name',
        'status',
        'availability',
    ];

    public function location()
    {
        return $this->belongsTo(TableLocation::class, 'location_id', 'id');
    }
}
