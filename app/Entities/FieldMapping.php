<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class FieldMapping.
 *
 * @package namespace App\Entities;
 */
class FieldMapping extends Model implements Transformable
{
    use TransformableTrait,
        SoftDeletes,
        BidObserverTrait;

    protected $table = 'field_mapping';

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
        'file_storage_setup_bid',
        'catapult_db_setup_bid',
        'api_setup_bid',
        'status',
        'data_entry',
        'created_by',
        'updated_by',
    ];

    public function fileStorageSetup()
    {
        return $this->belongsTo(FileStorageSetup::class, 'file_storage_setup_bid', 'bid');
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
        return $this->hasMany(FieldMappingDetail::class, 'field_mapping_bid', 'bid');
    }

    public function detail()
    {
        return $this->hasMany(FieldMappingDetail::class, 'field_mapping_bid', 'bid');
    }

}
