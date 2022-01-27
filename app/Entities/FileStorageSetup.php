<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class FileStorageSetup.
 *
 * @package namespace App\Entities;
 */
class FileStorageSetup extends Base
{
    public $table = 'file_storage_setup';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'name',
        'storage_type',
        'local_path',
        'remote_path',
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
