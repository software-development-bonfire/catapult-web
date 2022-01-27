<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class SyncFileReference.
 *
 * @package namespace App\Entities;
 */
class SyncFileReference extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $primaryKey = 'bid';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'storage_type',
        'filename',
        'extension',
        'path',
        'counter',
        'last_modified'
    ];

}
