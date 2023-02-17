<?php

namespace App\Entities;

use App\Traits\BidObserverTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class FieldMappingPreset.
 *
 * @package namespace App\Entities;
 */
class FieldMappingPreset extends Model implements Transformable
{
    use TransformableTrait,
        SoftDeletes,
        BidObserverTrait;

    protected $table = 'field_mapping_preset';

    protected $primaryKey = 'bid';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bid',
        'type',
        'data_entry',
        'preset_name',
        'status',
        'created_by',
        'updated_by'
    ];

    public function detail()
    {
        return $this->hasMany(FieldMappingPresetDetail::class, 'field_mapping_preset_bid', 'bid');
    }

}
