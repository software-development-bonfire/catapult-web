<?php

namespace App\Entities;

use App\Traits\QueryHelper;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BidObserverTrait;

class Base extends Model
{
    use BidObserverTrait;
    use QueryHelper;

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
