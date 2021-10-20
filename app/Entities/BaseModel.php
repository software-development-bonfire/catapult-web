<?php

namespace App\Entities;

use App\Traits\RelationshipTrait;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use RelationshipTrait;

    protected $primaryKey = 'bid';

    public $incrementing = false;

    protected $casts = [
        'bid' => 'string'
    ];

    public static function boot()
    {
        parent::boot();
    }

    public function scopeTableName()
    {
        return with(new static)->getTable();
    }

    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }
}
