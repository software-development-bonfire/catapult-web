<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveCategoryBidInCdisCostAndPriceChangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_cost_and_price_change', function (Blueprint $table) {
            $table->dropForeign('cdis_cost_and_price_change_category_bid_foreign');
            $table->dropColumn('category_bid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_cost_and_price_change', function (Blueprint $table) {
            $table->unsignedBigInteger('category_bid')->nullable()->after('vendor_bid');

            $table->foreign('cdis_category_bid')
                ->references('bid')
                ->on('cdis_product_category')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }
}
