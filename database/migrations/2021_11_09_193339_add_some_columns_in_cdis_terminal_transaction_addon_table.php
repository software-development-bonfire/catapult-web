<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisTerminalTransactionAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
            $table->decimal('tax_percentage', 23, 6)->default(0.000000)->after('quantity');
            $table->decimal('vatable_sales', 23, 6)->default(0.000000)->after('total_amount');
            $table->decimal('zero_rated_sales', 23, 6)->default(0.000000)->after('vatable_sales');
            $table->decimal('tax', 23, 6)->default(0.000000)->after('zero_rated_sales');
            $table->decimal('vat_exempt', 23, 6)->default(0.000000)->after('tax');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
            $table->dropColumn('tax_percentage');
            $table->dropColumn('vatable_sales');
            $table->dropColumn('zero_rated_sales');
            $table->dropColumn('tax');
            $table->dropColumn('vat_exempt');
        });
    }
}
