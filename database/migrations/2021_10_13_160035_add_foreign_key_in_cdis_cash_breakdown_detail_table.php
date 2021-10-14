<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyInCdisCashBreakdownDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_cash_breakdown_detail', function (Blueprint $table) {
            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_cash_breakdown')
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
        Schema::table('cdis_cash_breakdown_detail', function (Blueprint $table) {
            $table->dropForeign('cdis_cash_breakdown_detail_head_bid_foreign');
            $table->dropIndex('cdis_cash_breakdown_detail_head_bid_foreign');
        });
    }
}
