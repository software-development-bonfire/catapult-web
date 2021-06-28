<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class SyncIntervalSetting.
 *
 * @package namespace App\Entities;
 */
class SyncIntervalSetting extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'name',
        'checking_interval',
        'syncing_type',
        'start_time',
        'status',
        'created_by',
        'updated_by'
    ];

}
