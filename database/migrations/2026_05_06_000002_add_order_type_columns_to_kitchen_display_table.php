<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderTypeColumnsToKitchenDisplayTable extends Migration
{
    protected $tableName = 'kitchen_display';

    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            return;
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            if (!Schema::hasColumn($this->tableName, 'terminal_bid')) {
                $table->unsignedBigInteger('terminal_bid')->nullable()->after('transaction_detail_bid');
            }
            if (!Schema::hasColumn($this->tableName, 'order_type_id')) {
                $table->string('order_type_id', 50)->nullable()->after('order_id');
            }
            if (!Schema::hasColumn($this->tableName, 'order_type_name')) {
                $table->string('order_type_name', 100)->nullable()->after('order_type_id');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable($this->tableName)) {
            return;
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'terminal_bid')) {
                $table->dropColumn('terminal_bid');
            }
            if (Schema::hasColumn($this->tableName, 'order_type_id')) {
                $table->dropColumn('order_type_id');
            }
            if (Schema::hasColumn($this->tableName, 'order_type_name')) {
                $table->dropColumn('order_type_name');
            }
        });
    }
}
