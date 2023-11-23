<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class FieldMappingDetail.
 *
 * @package namespace App\Entities;
 */
class DeviceSettings extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $table = 'device_settings';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'device_type',
        'name',
        'ip_address',
        'api_endpoint',
        'token',
        'status',
        'created_by',
        'updated_by'
    ];

}
