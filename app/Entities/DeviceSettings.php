<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class DeviceSettings.
 *
 * @package namespace App\Entities;
 */
class DeviceSettings extends Base
{
    use SoftDeletes;
    protected $table = 'device_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'terminal_code',
        'device_code',
        'device_type',
        'name',
        'ip_address',
        'api_endpoint',
        'token',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'bid' => 'string',
    ];

}
