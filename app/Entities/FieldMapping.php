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

    protected $primaryKey = 'bid';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'type',
        'api_endpoint',
        'api_version_name',
        'field_entry',
        'status',
        'created_by',
        'updated_by'
    ];

    public function details()
    {
        return $this->hasMany(FieldMappingDetail::class, 'field_mapping_bid', 'bid');
    }

}
