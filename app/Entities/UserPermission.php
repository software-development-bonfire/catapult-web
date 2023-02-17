<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use BidObserverTrait;
    
    protected $primaryKey = 'bid';
    
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_bid',
        'code',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'code' => 'integer'
    ];
}
