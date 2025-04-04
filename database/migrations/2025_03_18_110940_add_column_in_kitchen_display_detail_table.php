<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnInKitchenDisplayDetailTable extends Migration
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
                if (! Schema::hasColumn($this->tableName, 'usage_type')) {
                    $table->integer('usage_type')->nullable()->after('status');
                }
                if (! Schema::hasColumn($this->tableName, 'order_type_name')) {
                    $table->string('order_type_name')->nullable()->after('usage_type');
                }
                if (! Schema::hasColumn($this->tableName, 'product_uom_packaging_bid')) {
                    $table->unsignedBigInteger('product_uom_packaging_bid')->nullable()->after('transaction_product_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'transaction_id')) {
                    $table->string('transaction_id')->nullable()->after('head_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'kitchen_station_index')) {
                    $table->integer('kitchen_station_index')->nullable()->after('kitchen_station_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'special_request')) {
                    $table->string('special_request')->nullable()->after('kitchen_station_index');
                }
                if (! Schema::hasColumn($this->tableName, 'name')) {
                    $table->string('name')->nullable()->after('special_request');
                }
                if (! Schema::hasColumn($this->tableName, 'addons')) {
                    $table->string('addons')->nullable()->after('name');
                }
                if (! Schema::hasColumn($this->tableName, 'is_addon')) {
                    $table->string('is_addon')->nullable()->after('addons');
                }
                if (! Schema::hasColumn($this->tableName, 'terminal_number')) {
                    $table->string('terminal_number')->nullable()->after('is_addon');
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
                if (Schema::hasColumn($this->tableName, 'transaction_id')) {
                    $table->dropColumn('transaction_id');
                }
                 if (Schema::hasColumn($this->tableName, 'product_uom_packaging_bid')) {
                    $table->dropColumn('product_uom_packaging_bid');
                }
                if (Schema::hasColumn($this->tableName, 'usage_type')) {
                    $table->dropColumn('usage_type');
                }
                if (Schema::hasColumn($this->tableName, 'order_type_name')) {
                    $table->dropColumn('order_type_name');
                }
                if (Schema::hasColumn($this->tableName, 'kitchen_station_index')) {
                    $table->dropColumn('kitchen_station_index');
                }
                if (Schema::hasColumn($this->tableName, 'kitchen_station_no')) {
                    $table->dropColumn('kitchen_station_no');
                }
                if (Schema::hasColumn($this->tableName, 'special_request')) {
                    $table->dropColumn('special_request');
                }
                if (Schema::hasColumn($this->tableName, 'name')) {
                    $table->dropColumn('name');
                }
                if (Schema::hasColumn($this->tableName, 'addons')) {
                    $table->dropColumn('addons');
                }
                if (Schema::hasColumn($this->tableName, 'is_addon')) {
                    $table->dropColumn('is_addon');
                }
                if (Schema::hasColumn($this->tableName, 'terminal_number')) {
                    $table->dropColumn('terminal_number');
                }
            });
        }
    }
}
