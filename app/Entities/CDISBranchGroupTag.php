<?php

namespace App\Entities;

class CDISBranchGroupTag extends BaseModel
{
    protected $table = 'cdis_branch_group_tag';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'bid';

    protected $fillable = [
        'bid',
        'branch_group_bid',
        'branch_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'branch_group_bid' => 'string',
        'branch_bid' => 'string',
    ];

    public function branchGroup()
    {
        return $this->belongsTo(CDISBranchGroupTag::class, 'branch_group_bid', 'bid');
    }

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid', 'bid');
    }
}
