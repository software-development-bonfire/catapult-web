<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxOrderAllowedColumnToProductBranchAvailabilityTable extends Migration
{
    protected $tableName = 'cdis_product_branch_availability';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
                Schema::table($this->tableName, function (Blueprint $table) {
                    if (! Schema::hasColumn($this->tableName, 'max_order_allowed')) {
                        $table->decimal('max_order_allowed', 23,6)->default(0)->after('min_stock');
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
                if (Schema::hasColumn($this->tableName, 'max_order_allowed')) {
                    $table->dropColumn('max_order_allowed');
                }
            });
        }
    }
}
