<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        if (Schema::hasTable('cdis_terminal_transaction')) {
            Schema::table('cdis_terminal_transaction', function (Blueprint $table) {
                if (! Schema::hasColumn('cdis_terminal_transaction', 'special_instruction')) {
                    $table->text('special_instruction')->nullable()->after('remarks');
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
        if (Schema::hasTable('cdis_terminal_transaction')) {
            Schema::table('cdis_terminal_transaction', function (Blueprint $table) {
                if (Schema::hasColumn('cdis_terminal_transaction', 'special_instruction')) {
                    $table->dropColumn('special_instruction');
                }
            });
        }
    }
}
