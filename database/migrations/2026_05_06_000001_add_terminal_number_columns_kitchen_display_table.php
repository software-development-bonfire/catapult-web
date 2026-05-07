<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTerminalNumberColumnsKitchenDisplayTable extends Migration
{
    protected $tableName = 'kitchen_display';

    public function up()
    {
        if (! Schema::hasTable($this->tableName)) {
            return;
        }
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable()->after('system_mode')->comment('Transaction ID from POS');
            $table->unsignedBigInteger('terminal_number')->nullable()->after('transaction_id')->comment('Terminal # of the POS');
        });

    }

    public function down()
    {
        if (! Schema::hasTable($this->tableName)) {
            return;
        }
        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'terminal_number')) {
                $table->dropColumn('terminal_number');
            }
            if (Schema::hasColumn($this->tableName, 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
        });

    }
}
