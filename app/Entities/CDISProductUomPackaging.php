<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class CDISProductUomPackaging extends BaseModel
{
    protected $table = 'cdis_product_uom_packaging';

    protected $fillable = [
        'bid',
        'barcode',
        'description',
        'long_description',
        'product_bid',
        'uom_bid',
		'parent_bid',
        'variant_option',
        'trade_type',
        'service_type',
        'pack_content',
        'menu_description',
        'allergens',
        'calories',
        'max_prep_time',
        'max_assembly_time',
        'max_waiting_time',
        'max_serving_time',
		'image_path',
        'is_finished_good',
		'is_reduce_composition',
        'is_display_structure',
        'is_include_on_reports',
        'is_menu_item',
        'is_raw_material',
        'is_addon',
        'is_weighted',
        'is_price_point',
        'is_tag_reference',
        'is_sell_item',
        'is_inventory_item',
        'is_senior_item',
        'is_pwd_item',
		'is_solo_parent_item',
        'is_diplomat_item',
        'is_athlete_item',
        'is_default',
		'has_expiry',
        'is_print_sticker',
        'sort_index',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bid' => 'string',
        'product_bid' => 'string',
        'parent_bid' => 'string',
        'uom_bid' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
        'is_default' => 'boolean',
        'is_display_structure' => 'boolean',
        'is_include_on_reports' => 'boolean',
        'is_reduce_composition' => 'boolean',
    ];

    public function kitchenItemSetupDetail()
    {
        return $this->belongsTo(CDISKitchenItemSetupDetail::class, 'bid', 'product_uom_packaging_bid');
    }

    public function kitchenItemSetup()
    {
        return $this->hasManyThrough(CDISKitchenItemSetup::class, CDISKitchenItemSetupDetail::class, 'product_uom_packaging_bid', 'bid','bid', 'head_bid');
    }

    public function kitchenItemSetupSearchByBranch()
    {
        $kitchenItemSetup = DB::table('cdis_kitchen_item_setup')
            ->select('cdis_kitchen_item_setup.*')
            ->leftJoin('cdis_kitchen_item_setup_detail', function($join) {
                $join->on('cdis_kitchen_item_setup_detail.head_bid', '=', 'cdis_kitchen_item_setup.bid');
                return $join;
            })
            ->where('cdis_kitchen_item_setup.status', 1)
            ->whereNull('cdis_kitchen_item_setup.deleted_at')
            ->where('cdis_kitchen_item_setup_detail.product_uom_packaging_bid', $this->bid)
            ->orderBy('created_at', 'DESC');

        return $kitchenItemSetup;
    }

    public function product()
    {
        return $this->belongsTo(CDISProduct::class, 'product_bid', 'bid');
    }

    public function productBranchAvailability()
    {
        return $this->hasMany(CDISProductBranchAvailability::class, 'product_uom_bid', 'bid');
    }

    public function productStructureDetail()
    {
        return $this->hasMany(CDISProductStructureDetail::class, 'product_uom_bid', 'bid');
    }

    public function productAddonDetail()
    {
        return $this->hasMany(CDISProductAddonDetail::class, 'product_uom_bid', 'bid');
    }

    public function productModifier()
    {
        return $this->hasMany(CDISProductModifier::class, 'product_uom_bid', 'bid');
    }

    public function productModifierDetail()
    {
        return $this->hasMany(CDISProductModifierDetail::class, 'product_uom_bid', 'bid');
    }

    public function unitOfMeasurement()
    {
        return $this->belongsTo(CDISUnitOfMeasurement::class, 'uom_bid', 'bid');
    }

    public function productUomPackagingTag()
    {
        return $this->hasMany(CDISProductUomPackagingTag::class, 'product_uom_packaging_bid', 'bid');
    }

    public function syncDetails()
    {
        $syncDetails = (object) array(
            'group' => null,
            'head_bid' => null,
            'level' => 1,
            'reference_bid' => null,
            'reference_table' => null,
        );

        $routeName = Route::currentRouteName();

        if ($routeName == 'create_product' || $routeName == 'destroy_product') {
            $syncDetails->group = 'cdis_product';
            $syncDetails->head_bid = $this->product_bid;
            $syncDetails->level = 2;
        } else if ($routeName == 'store_uom_packaging') {
            $syncDetails->group = $this->getTable() ?? 'cdis_product_uom_packaging';
            $syncDetails->head_bid = $this->bid;
            $syncDetails->level = 1;
        } else if ($routeName == 'delete_uom_packaging') {
            $syncDetails->group = $this->getTable() ?? 'cdis_product_uom_packaging';
            $syncDetails->head_bid = $this->bid;
            $syncDetails->level = 2;
        }

        $syncDetails->reference_bid = json_encode([$this->product_bid, $this->uom_bid, $this->parent_bid]);
        $syncDetails->reference_table = json_encode(['cdis_product', 'cdis_unit_of_measurement', 'cdis_product_uom_packaging']);

        return $syncDetails;
    }
}
