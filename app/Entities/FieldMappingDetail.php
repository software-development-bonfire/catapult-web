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
class FieldMappingDetail extends Model implements Transformable
{
    use TransformableTrait,
        BidObserverTrait;

    protected $primaryKey = 'bid';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'field_mapping_bid',
        'required',
        'field',
        'description',
        'mapping_type',
        'file_name',
        'default_value',
        'column_name'
    ];

    public function fieldMapping()
    {
        return $this->belongsTo(FieldMapping::class, 'bid', 'field_mapping_bid');
    }

}
