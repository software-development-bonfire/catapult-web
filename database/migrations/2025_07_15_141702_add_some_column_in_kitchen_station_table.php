<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnInKitchenStationTable extends Migration
{
    protected $tableName = 'cdis_kitchen_station';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'order_type')) {
                    $table->boolean('order_type')->default(1)->after('queueing_group_type');
                }
            });
        };
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
                if (Schema::hasColumn($this->tableName, 'order_type')) {
                    $table->dropColumn('order_type');
                }
            });
        };
    }
}
