<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductBranchPriceBidInCdisProductAddonDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product_addon_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('product_branch_price_bid')->after('product_uom_bid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product_addon_detail', function (Blueprint $table) {
            $table->dropColumn('product_branch_price_bid');
        });
    }
}
