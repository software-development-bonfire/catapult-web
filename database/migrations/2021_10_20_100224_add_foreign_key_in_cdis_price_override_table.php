<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyInCdisPriceOverrideTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_price_override', function (Blueprint $table) {
            $table->foreign('transaction_detail_bid')
                ->references('bid')
                ->on('cdis_terminal_transaction_detail')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('transaction_product_bid')
                ->references('bid')
                ->on('cdis_terminal_transaction_product')
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
        Schema::table('cdis_price_override', function (Blueprint $table) {
            $table->dropForeign('cdis_price_override_transaction_product_bid_foreign');
            $table->dropIndex('cdis_price_override_transaction_product_bid_foreign');

            $table->dropForeign('cdis_price_override_transaction_detail_bid_foreign');
            $table->dropIndex('cdis_price_override_transaction_detail_bid_foreign');
        });
    }
}
