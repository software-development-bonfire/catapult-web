<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrepTimeColumnInProductUomAndKitchenItemSetupTable extends Migration
{
    private const PRODUCT_UOM_TABLE = 'cdis_product_uom_packaging';
    private const KITCHEN_ITEM_TABLE = 'cdis_kitchen_item_setup_detail';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable(self::PRODUCT_UOM_TABLE)) {
            Schema::table(self::PRODUCT_UOM_TABLE, function (Blueprint $table) {
                if (!Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'calories')) {
                    $table->decimal('calories', 23, 6)->default(0.000000)->after('pack_content');
                }

                if (!Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'menu_description')) {
                    $table->string('menu_description')->nullable()->after('calories');
                }

                if (!Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'allergens')) {
                    $table->string('allergens')->nullable()->after('menu_description');
                }

                if (!Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_prep_time')) {
                    $table->decimal('max_prep_time', 23, 6)->default(0.000000)->after('allergens');
                }
            });
        }
        if (Schema::hasTable(self::KITCHEN_ITEM_TABLE)) {
            Schema::table(self::KITCHEN_ITEM_TABLE, function (Blueprint $table) {
                if (!Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_prep_time')) {
                    $table->decimal('max_prep_time', 23, 6)->default(0.000000)->after('product_uom_packaging_bid');
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
                if (Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'calories')) {
                    $table->dropColumn('calories');
                }

                if (Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'menu_description')) {
                    $table->dropColumn('menu_description');
                }

                if (Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'allergens')) {
                    $table->dropColumn('allergens');
                }

                if (Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_prep_time')) {
                    $table->dropColumn('max_prep_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_ITEM_TABLE)) {
            Schema::table(self::KITCHEN_ITEM_TABLE, function (Blueprint $table) {
                if (Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_prep_time')) {
                    $table->dropColumn('max_prep_time');
                }
            });
        }
    }
}
