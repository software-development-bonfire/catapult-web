<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsageTypeColumnsInCdisTerminalTransactionAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
            if (! Schema::hasColumn('cdis_terminal_transaction_addon', 'usage_type')) {
                Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
                    $table->tinyInteger('usage_type')->after('remarks')->default(\App\Enums\UsageType::ADDON);
                });
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
        if (Schema::hasColumn('cdis_terminal_transaction_addon', 'usage_type')) {
            Schema::table('cdis_terminal_transaction_addon', function (Blueprint $table) {
                $table->dropColumn('usage_type');
            });
        }
    }
}
