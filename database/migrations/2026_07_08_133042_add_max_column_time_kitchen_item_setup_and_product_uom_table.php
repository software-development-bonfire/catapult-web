<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxColumnTimeKitchenItemSetupAndProductUomTable extends Migration
{
    private const PRODUCT_UOM_TABLE = 'cdis_product_uom_packaging';
    private const KITCHEN_ITEM_TABLE = 'cdis_kitchen_item_setup_detail';
    private const KITCHEN_DISPLAY_DETAIL_TABLE = 'kitchen_display_detail';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable(self::PRODUCT_UOM_TABLE)) {
            Schema::table(self::PRODUCT_UOM_TABLE, function (Blueprint $table) {
                if (!Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_waiting_time')) {
                    $table->decimal('max_waiting_time', 23, 6)->default(0.000000)->after('allergens');
                }
                if (!Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_serving_time')) {
                    $table->decimal('max_serving_time', 23, 6)->default(0.000000)->after('max_prep_time');
                }
            });
        }
        if (Schema::hasTable(self::KITCHEN_ITEM_TABLE)) {
            Schema::table(self::KITCHEN_ITEM_TABLE, function (Blueprint $table) {
                if (!Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_waiting_time')) {
                    $table->decimal('max_waiting_time', 23, 6)->default(0.000000)->after('product_uom_packaging_bid');
                }
                 if (!Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_serving_time')) {
                    $table->decimal('max_serving_time', 23, 6)->default(0.000000)->after('max_prep_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_DISPLAY_DETAIL_TABLE)) {
            Schema::table(self::KITCHEN_DISPLAY_DETAIL_TABLE, function (Blueprint $table) {
                if (!Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'max_waiting_time')) {
                    $table->decimal('max_waiting_time', 23, 6)->default(0.000000)->after('end_at');
                }
                 if (!Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'max_serving_time')) {
                    $table->decimal('max_serving_time', 23, 6)->default(0.000000)->after('max_preparation_time');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable(self::PRODUCT_UOM_TABLE)) {
            Schema::table(self::PRODUCT_UOM_TABLE, function (Blueprint $table) {
                if (Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_waiting_time')) {
                    $table->dropColumn('max_waiting_time');
                }
                 if (Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_serving_time')) {
                    $table->dropColumn('max_serving_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_ITEM_TABLE)) {
            Schema::table(self::KITCHEN_ITEM_TABLE, function (Blueprint $table) {
                if (Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_waiting_time')) {
                    $table->dropColumn('max_waiting_time');
                }
                if (Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_serving_time')) {
                    $table->dropColumn('max_serving_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_DISPLAY_DETAIL_TABLE)) {
            Schema::table(self::KITCHEN_DISPLAY_DETAIL_TABLE, function (Blueprint $table) {
                if (Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'max_waiting_time')) {
                    $table->dropColumn('max_waiting_time');
                }
                if (Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'max_serving_time')) {
                    $table->dropColumn('max_serving_time');
                }
            });
        }
    }
}
