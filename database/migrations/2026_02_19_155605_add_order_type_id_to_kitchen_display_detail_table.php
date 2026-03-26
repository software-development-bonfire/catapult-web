<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderTypeIdToKitchenDisplayDetailTable extends Migration
{
    protected $tableName = 'kitchen_display_detail';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                // Add order_type_id if it doesn't already exist
                if (!Schema::hasColumn($this->tableName, 'order_type_id')) {
                    $table->string('order_type_id')->nullable()->after('order_type_name');
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
                if (Schema::hasColumn($this->tableName, 'order_type_id')) {
                    $table->dropColumn('order_type_id');
                }
            });
        }
    }
}
