<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailabilityToCdisTerminalTransactionTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('cdis_terminal_transaction')) {
            Schema::table('cdis_terminal_transaction', function (Blueprint $table) {
                if (!Schema::hasColumn('cdis_terminal_transaction', 'availability')) {
                    $table->enum('availability', ['available', 'unavailable'])->default('available')->after('status')->index();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('cdis_terminal_transaction') && Schema::hasColumn('cdis_terminal_transaction', 'availability')) {
            Schema::table('cdis_terminal_transaction', function (Blueprint $table) {
                $table->dropColumn('availability');
            });
        }
    }
}
