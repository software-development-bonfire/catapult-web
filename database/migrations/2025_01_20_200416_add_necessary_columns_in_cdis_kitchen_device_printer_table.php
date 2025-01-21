<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNecessaryColumnsInCDISKitchenDevicePrinterTable extends Migration
{
    private $table = 'cdis_kitchen_device_printer';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (! Schema::hasColumn($this->table, 'local_printer')) {
                    $table->string('local_printer')->after('printer_host')->nullable();
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
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (Schema::hasColumn($this->table, 'local_printer')) {
                    $table->dropColumn('local_printer');
                }
            });
        }
    }
}
