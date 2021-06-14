<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

/**
 * Class Configuration.
 *
 * @package namespace App\Entities;
 */
class Configuration extends Model implements Transformable
{
    use TransformableTrait;

    protected  $primaryKey = 'attribute';
    public $incrementing = false;
    public $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'attribute',
        'value'
    ];
    
    public $timestamps = false;

}
