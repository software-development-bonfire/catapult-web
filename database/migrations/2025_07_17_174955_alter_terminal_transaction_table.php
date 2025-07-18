<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTerminalTransactionTable extends Migration
{
    protected $tableName = 'cdis_terminal_transaction';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'transaction_id')) {
                $table->string('transaction_id', 64)->default('0')->change();
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
        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'transaction_id')) {
                $table->bigInteger('transaction_id')->default(0)->change();
            }
        });
    }
}
