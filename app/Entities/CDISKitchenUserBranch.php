<?php

namespace App\Entities;

class CDISKitchenUserBranch extends BaseModel
{
    protected $table = 'cdis_kitchen_user_branch';

    protected $primaryKey = 'bid';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'bid' => 'string',
        'branch_bid' => 'string',
        'kitchen_user_bid' => 'string'
    ];

    protected $fillable = [
        'bid',
        'branch_bid',
        'kitchen_user_bid'
    ];

    public function branch()
    {
        return $this->belongsTo(CDISBranch::class, 'branch_bid', 'bid');
    }

    public function kitchenUser()
    {
        return $this->belongsTo(CDISKitchenUser::class, 'kitchen_user_bid', 'bid');
    }
}
