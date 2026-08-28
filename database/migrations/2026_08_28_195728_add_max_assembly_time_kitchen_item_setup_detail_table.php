<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxAssemblyTimeKitchenItemSetupDetailTable extends Migration
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
                if (!Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_assembly_time')) {
                    $table->decimal('max_assembly_time', 23, 6)->default(0.000000)->after('max_prep_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_ITEM_TABLE)) {
            Schema::table(self::KITCHEN_ITEM_TABLE, function (Blueprint $table) {
                if (!Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_assembly_time')) {
                    $table->decimal('max_assembly_time', 23, 6)->default(0.000000)->after('max_prep_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_DISPLAY_DETAIL_TABLE)) {
            Schema::table(self::KITCHEN_DISPLAY_DETAIL_TABLE, function (Blueprint $table) {
                if (!Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'max_assembly_time')) {
                    $table->decimal('max_assembly_time', 23, 6)->default(0.000000)->after('max_preparation_time');
                }
                if (!Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'assembled_quantity')) {
                    $table->decimal('assembled_quantity', 23, 6)->default(0.000000)->after('bumped_quantity');
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
                if (Schema::hasColumn(self::PRODUCT_UOM_TABLE, 'max_assembly_time')) {
                    $table->dropColumn('max_assembly_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_ITEM_TABLE)) {
            Schema::table(self::KITCHEN_ITEM_TABLE, function (Blueprint $table) {
                if (Schema::hasColumn(self::KITCHEN_ITEM_TABLE, 'max_assembly_time')) {
                    $table->dropColumn('max_assembly_time');
                }
            });
        }

        if (Schema::hasTable(self::KITCHEN_DISPLAY_DETAIL_TABLE)) {
            Schema::table(self::KITCHEN_DISPLAY_DETAIL_TABLE, function (Blueprint $table) {
                if (Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'max_assembly_time')) {
                    $table->dropColumn('max_assembly_time');
                }
                if (Schema::hasColumn(self::KITCHEN_DISPLAY_DETAIL_TABLE, 'assembled_quantity')) {
                    $table->dropColumn('assembled_quantity');
                }
            });
        }
    }
}
