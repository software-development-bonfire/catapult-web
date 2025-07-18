<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnInCdisProductBranchAvailabilityTable extends Migration
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
                if (! Schema::hasColumn($this->tableName, 'ecomm_stock_availability')) {
                    $table->tinyInteger('ecomm_stock_availability')->after('is_available')->default(1);
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
                if (Schema::hasColumn($this->tableName, 'ecomm_stock_availability')) {
                    $table->dropColumn('ecomm_stock_availability');
                }
            });
        }
    }
}
