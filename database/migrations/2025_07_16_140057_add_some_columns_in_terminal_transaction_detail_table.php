<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInTerminalTransactionDetailTable extends Migration
{
    protected $tableName = 'cdis_terminal_transaction_detail';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'si_reset_number')) {
                    $table->string('si_reset_number')->nullable()->after('total_tender');
                }
                if (! Schema::hasColumn($this->tableName, 'service_charge_percentage')) {
                    $table->decimal('service_charge_percentage', 23,6)->nullable()->after('si_reset_number');
                }
                if (! Schema::hasColumn($this->tableName, 'manual_receipt')) {
                    $table->string('manual_receipt')->nullable()->after('service_charge_percentage');
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
                if (Schema::hasColumn($this->tableName, 'si_reset_number')) {
                    $table->dropColumn('si_reset_number');
                }
                if (Schema::hasColumn($this->tableName, 'service_charge_percentage')) {
                    $table->dropColumn('service_charge_percentage');
                }
                if (Schema::hasColumn($this->tableName, 'manual_receipt')) {
                    $table->dropColumn('manual_receipt');
                }
            });
        }
    }
}
