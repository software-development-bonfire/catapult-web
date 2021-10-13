<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class CatapultDbSetup.
 *
 * @package namespace App\Entities;
 */
class CatapultDbSetup extends Base implements Transformable
{
    use TransformableTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'name',
        'host',
        'port',
        'db_name',
        'username',
        'password',
        'status',
        'created_by',
        'updated_by'
    ];

}
