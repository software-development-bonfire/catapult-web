<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add transaction_type column to kitchen_display_detail table.
 *
 * transaction_type: The type of transaction (integer) for categorizing orders.
 *   Used to identify and filter different transaction types in the kitchen display.
 */
class AddTransactionTypeColumn extends Migration
{
    public function up()
    {
        // Add transaction_type to kitchen_display_detail
        if (!Schema::hasTable('kitchen_display_detail')) {
            return;
        }

        if (!Schema::hasColumn('kitchen_display_detail', 'transaction_type')) {
            Schema::table('kitchen_display_detail', function (Blueprint $table) {
                $table->unsignedInteger('transaction_type')->nullable()->comment('Transaction type identifier')->after('transaction_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('kitchen_display_detail') && Schema::hasColumn('kitchen_display_detail', 'transaction_type')) {
            Schema::table('kitchen_display_detail', function (Blueprint $table) {
                $table->dropColumn('transaction_type');
            });
        }
    }
}
