<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

class CDISKitchenUser extends Model
{
    use HasApiTokens, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'cdis_kitchen_user';

    /**
     * @var string
     */
    protected $primaryKey = 'bid';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'user_code',
        'full_name',
        'username',
        'password',
        'status',
        'type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'bid' => 'string'
    ];
}
