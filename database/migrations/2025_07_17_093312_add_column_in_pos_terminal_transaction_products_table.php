<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnInPosTerminalTransactionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('pos_terminal_transaction_products')) {
            Schema::table('pos_terminal_transaction_products', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_terminal_transaction_products', 'parent_bid')) {
                    $table->unsignedBigInteger('parent_bid')->nullable()->after('product_bid');
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
        Schema::table('pos_terminal_transaction_products', function (Blueprint $table) {
            if (Schema::hasColumn('pos_terminal_transaction_products', 'parent_bid')) {
                $table->dropColumn('parent_bid');
            }
            
        });
    }
}
