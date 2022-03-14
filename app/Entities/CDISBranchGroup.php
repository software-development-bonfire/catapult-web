<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class CDISBranchGroup extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cdis_branch_group';

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'code',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string'
    ];

    protected $auditExclude = [
        'id',
        'bid',
        'created_by',
        'updated_by',
    ];

    public function branchGroupTag()
    {
        return $this->hasMany(CDISBranchGroupTag::class, 'branch_group_bid', 'bid');
    }
}
