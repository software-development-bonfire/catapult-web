<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class SystemLog.
 *
 * @package namespace App\Entities;
 */
class SystemLog extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'initiator',
        'module_process',
        'action',
        'description',
    ];

}
