<?php

namespace App\Entities;

use Illuminate\Support\Facades\Route;

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

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => '',
            'group' => '',
            'head_bid' => '',
            'level' => 0,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if (
            $routeName == 'store_kitchen_user_account'
            || $routeName == 'update_kitchen_user_account'
        ) {
            
            $syncDetails->code = null;
            $syncDetails->group = 'cdis_kitchen_user';
            $syncDetails->head_bid = $this->kitchen_user_bid;
            $syncDetails->reference_bid = $this->kitchen_user_bid;
            $syncDetails->reference_table =  'cdis_kitchen_user';
            $syncDetails->level = 2;
        }

        return $syncDetails;
    }
}
