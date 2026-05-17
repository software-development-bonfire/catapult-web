<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSpecialRequestColumnsInTerminalTransactionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add special_request column to cdis_terminal_transaction_product table
        Schema::table('cdis_terminal_transaction_product', function (Blueprint $table) {
            if (!Schema::hasColumn('cdis_terminal_transaction_product', 'special_request')) {
                $table->text('special_request')->nullable()->after('remarks')->comment('Special requests or notes for the product');
            }
        });

        // Add special_request column to cdis_terminal_transaction_addon table
        Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
            if (!Schema::hasColumn('cdis_terminal_transaction_addon', 'special_request')) {
                $table->text('special_request')->nullable()->after('remarks')->comment('Special requests or notes for the addon');
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
            if (Schema::hasColumn('cdis_terminal_transaction_product', 'special_request')) {
                $table->dropColumn('special_request');
            }
        });

        Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
            if (Schema::hasColumn('cdis_terminal_transaction_addon', 'special_request')) {
                $table->dropColumn('special_request');
            }
        });
    }
}
