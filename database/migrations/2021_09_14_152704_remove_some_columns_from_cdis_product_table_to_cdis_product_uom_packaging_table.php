<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveSomeColumnsFromCdisProductTableToCdisProductUomPackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product', function (Blueprint $table) {
            $table->dropColumn('is_sell_item');
            $table->dropColumn('is_inventory_item');
            $table->dropColumn('is_senior_item');
            $table->dropColumn('is_pwd_item');
        });

        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->string('variant_option', 128)->after('uom_bid');
            $table->tinyInteger('is_sell_item')->default(1)->after('is_addon');
            $table->tinyInteger('is_inventory_item')->default(1)->after('is_sell_item');
            $table->tinyInteger('is_senior_item')->default(0)->after('is_inventory_item');
            $table->tinyInteger('is_pwd_item')->default(0)->after('is_senior_item');
            $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE)->after('is_default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->dropColumn('variant_option');
            $table->dropColumn('is_sell_item');
            $table->dropColumn('is_inventory_item');
            $table->dropColumn('is_senior_item');
            $table->dropColumn('is_pwd_item');
            $table->dropColumn('status');
        });

        Schema::table('cdis_product', function (Blueprint $table) {
            $table->tinyInteger('is_sell_item')->default(1)->after('status');
            $table->tinyInteger('is_inventory_item')->default(1)->after('is_sell_item');
            $table->tinyInteger('is_senior_item')->default(0)->after('tax_code');
            $table->tinyInteger('is_pwd_item')->default(0)->after('is_senior_item');
        });
    }
}
