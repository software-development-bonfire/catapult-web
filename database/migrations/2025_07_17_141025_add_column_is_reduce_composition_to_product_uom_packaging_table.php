<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIsReduceCompositionToProductUomPackagingTable extends Migration
{
    protected $tableName = 'cdis_product_uom_packaging';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            if (! Schema::hasColumn($this->tableName, 'is_reduce_composition')) {
                $table->tinyInteger('is_reduce_composition')->default('1')->after('is_finished_good');
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
            if (Schema::hasColumn($this->tableName, 'is_reduce_composition')) {
                $table->dropColumn('is_reduce_composition');
            }
        });
    }
}
