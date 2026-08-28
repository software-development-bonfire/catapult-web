<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAdditionalToTerminalTransactionProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_terminal_transaction_product', function (Blueprint $table) {
            if (! Schema::hasColumn('cdis_terminal_transaction_product', 'is_additional')) {
                $table->tinyInteger('is_additional')->default(0)->after('is_free');
            }
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
            if (Schema::hasColumn('cdis_terminal_transaction_product', 'is_additional')) {
                $table->dropColumn('is_additional');
            }
        });
    }
}
