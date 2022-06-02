<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisTerminalTransactionDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_terminal_transaction_discount', function (Blueprint $table) {
            $table->tinyInteger('usage_type')->after('remarks')->default(\App\Enums\UsageType::PRODUCT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_terminal_transaction_discount', function (Blueprint $table) {
            $table->dropColumn('usage_type');
        });
    }
}
