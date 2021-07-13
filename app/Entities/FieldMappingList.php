<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class FieldMappingList.
 *
 * @package namespace App\Entities;
 */
class FieldMappingList extends Model implements Transformable
{
    use TransformableTrait,
        SoftDeletes,
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
        'name',
        'type',
        'field_mapping_bid',
        'remote_setup_bid',
        'catapult_db_setup_bid',
        'api_setup_bid',
        'status',
        'api_endpoint',
        'api_version_name',
        'created_by',
        'updated_by',
    ];

    public function remoteSetup()
    {
        return $this->belongsTo(RemoteSetup::class, 'remote_setup_bid', 'bid');
    }

    public function catapultDBSetup()
    {
        return $this->belongsTo(CatapultDbSetup::class, 'catapult_db_setup_bid', 'bid');
    }

    public function apiSetup()
    {
        return $this->belongsTo(ApiSetup::class, 'api_setup_bid', 'bid');
    }

    public function dataMappings()
    {
        return $this->hasMany(DataMapping::class, 'field_mapping_list_bid', 'bid');
    }

}
