<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysInAllCdisZreadTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_zread_tender_detail', function (Blueprint $table) {
            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_zread')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        Schema::table('cdis_zread_cash_breakdown_detail', function (Blueprint $table) {
            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_zread')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        Schema::table('cdis_zread_cashier_sales_summary', function (Blueprint $table) {
            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_zread')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });

        Schema::table('cdis_zread_regular_discount', function (Blueprint $table) {
            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_zread')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_zread_tender_detail', function (Blueprint $table) {
            $table->dropForeign('cdis_zread_tender_detail_head_bid_foreign');
            $table->dropIndex('cdis_zread_tender_detail_head_bid_foreign');
        });

        Schema::table('cdis_zread_cash_breakdown_detail', function (Blueprint $table) {
            $table->dropForeign('cdis_zread_cash_breakdown_detail_head_bid_foreign');
            $table->dropIndex('cdis_zread_cash_breakdown_detail_head_bid_foreign');
        });

        Schema::table('cdis_zread_cashier_sales_summary', function (Blueprint $table) {
            $table->dropForeign('cdis_zread_cashier_sales_summary_head_bid_foreign');
            $table->dropIndex('cdis_zread_cashier_sales_summary_head_bid_foreign');
        });

        Schema::table('cdis_zread_regular_discount', function (Blueprint $table) {
            $table->dropForeign('cdis_zread_regular_discount_head_bid_foreign');
            $table->dropIndex('cdis_zread_regular_discount_head_bid_foreign');
        });
    }
}
