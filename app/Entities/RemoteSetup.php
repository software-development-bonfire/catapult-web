<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class RemoteSetup.
 *
 * @package namespace App\Entities;
 */
class RemoteSetup extends Model implements Transformable
{
    use TransformableTrait;
    use BidObserverTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'name',
        'path',
        'server',
        'host',
        'port',
        'username',
        'password',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

}
