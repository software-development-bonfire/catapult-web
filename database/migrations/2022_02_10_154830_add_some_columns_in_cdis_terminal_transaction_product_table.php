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
            $table->unsignedBigInteger('supervisor_bid')->nullable()->after('remarks');
            $table->string('supervisor_name', 64)->nullable()->after('supervisor_bid');
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
            $table->dropColumn('supervisor_bid');
            $table->dropColumn('supervisor_name');
        });
    }
}