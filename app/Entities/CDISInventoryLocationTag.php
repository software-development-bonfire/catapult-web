<?php

namespace App\Entities;

use App\Enums\InventoryLocationTagType;
use Illuminate\Support\Facades\Route;

class CDISInventoryLocationTag extends BaseModel
{
    protected $table = 'cdis_inventory_location_tag';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'head_bid',
        'type',
        'inventory_location_bid',
    ];

    protected $casts = [
        'bid' => 'string',
        'head_bid' => 'string',
        'inventory_location_bid' => 'string',
    ];

    public function inventoryLocation()
    {
        return $this->belongsTo(CDISInventoryLocation::class, 'head_bid', 'bid');
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'code' => '',
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
            || $routeName == 'delete_branch'
        ) {
            $syncDetails->code = null;
            $syncDetails->group = 'branch';
            $syncDetails->head_bid = $this->head_bid;
            $syncDetails->level = 2;
        } else if (
            $routeName == 'store_terminal'
            || $routeName == 'update_terminal'
            || $routeName == 'delete_terminal'
        ) {
            $syncDetails->code = null;
            $syncDetails->group = 'terminal';
            $syncDetails->head_bid = $this->head_bid;
            $syncDetails->level = 2;
        }

        $referenceTables = [
            $this->type == InventoryLocationTagType::BRANCH ? 'cdis_branch' : 'cdis_terminal',
            'cdis_inventory_location'
        ];

        $syncDetails->reference_bid = json_encode([$this->head_bid, $this->inventory_location_bid]);
        $syncDetails->reference_table = json_encode($referenceTables);

        return $syncDetails;
    }
}
