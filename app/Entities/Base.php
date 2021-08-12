<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BidObserverTrait;

class Base extends Model
{
    use BidObserverTrait;

    protected $primaryKey = 'bid';

    public $incrementing = false;

    protected $casts = [
        'bid' => 'string'
    ];

    public static function boot()
    {
        parent::boot();
    }

    public function scopeTableName()
    {
        return with(new static)->getTable();
    }
}
