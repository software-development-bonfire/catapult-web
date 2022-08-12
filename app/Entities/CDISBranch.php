<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class CDISBranch extends BaseModel
{
    protected $table = 'cdis_branch';

    protected $fillable = [
        'bid',
        'code',
        'name',
        'address',
        'category',
        'contact_person',
        'contact_number',
        'business_name',
        'tin_no',
        'type',
        'status',
        'is_main_branch',
        'start_operation_hour',
        'end_operation_hour',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'is_main_branch' => 'boolean'
    ];

    public function terminals()
    {
        return $this->hasMany(CDISTerminal::class, 'branch_bid', 'bid');
    }

    public function kitchenItemSetup()
    {
        return $this->hasMany(CDISBranch::class, 'bid', 'branch_bid');
    }

    public function user()
    {
        return $this->belongsToMany(CDISUser::class, CDISUserBranch::class, 'branch_bid', 'user_bid');
    }

    public function terminalTransactions()
    {
        return $this->hasManyThrough(CDISTerminalTransaction::class, CDISTerminal::class, 'branch_bid', 'terminal_bid', 'bid');
    }
    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => null,
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if (
            $routeName == 'store_branch'
            || $routeName == 'update_branch'
        ) {
            $syncDetails->group = $this->getTable();
            $syncDetails->head_bid = $this->bid;
            $syncDetails->level = 1;
        }

        return $syncDetails;
    }
}
