<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSentAtToKdsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            'cdis_terminal_transaction',
            'cdis_terminal_transaction_product',
            'cdis_terminal_transaction_addon',
            'kitchen_display',
            'kitchen_display_detail',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'sent_at')) {
                    $table->timestamp('sent_at')->nullable()->after('updated_at');
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
        $tables = [
            'cdis_terminal_transaction',
            'cdis_terminal_transaction_product',
            'cdis_terminal_transaction_addon',
            'kitchen_display',
            'kitchen_display_detail',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'sent_at')) {
                    $table->dropColumn('sent_at');
                }
            });
        }
    }
}
