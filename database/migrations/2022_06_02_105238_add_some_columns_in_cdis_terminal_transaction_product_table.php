<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisTerminalTransactionProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_terminal_transaction_product', function (Blueprint $table) {
            $table->decimal('total_discount', 23, 6)->default(0.000000)->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_terminal_transaction_product', function (Blueprint $table) {
            $table->dropColumn('total_discount');
        });
    }
}
