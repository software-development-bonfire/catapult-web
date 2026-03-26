<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQueueNumberToPosTerminalTransactionsTable extends Migration
{
    protected $tableName = 'pos_terminal_transactions';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (!Schema::hasColumn($this->tableName, 'queue_number')) {
                    $table->string('queue_number')->nullable()->after('table_number');
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
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (Schema::hasColumn($this->tableName, 'queue_number')) {
                    $table->dropColumn('queue_number');
                }
            });
        }
    }
}
