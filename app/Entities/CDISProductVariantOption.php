<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CDISProductVariantOption extends Model
{
    use SoftDeletes;

    protected $table = 'cdis_product_variant_option';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'head_bid',
        'name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function syncDetails()
    {
        $code = '';
        $group = $this->head()->first()->getTable();
        $headBid = $this->head()->first()->bid;
        $level = 2;

        return (object) array(
            'code' => $code,
            'group' => $group,
            'head_bid' => $headBid,
            'level' => $level,
        );
    }

    public function head()
    {
        return $this->belongsTo(CDISProductVariant::class, 'head_bid','bid');
    }
}
