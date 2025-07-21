<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnClassificationToProductStructureDetailTable extends Migration
{
    protected $tableName = 'cdis_product_structure_detail';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            if (! Schema::hasColumn($this->tableName, 'classification')) {
                $table->tinyInteger('classification')->nullable()->after('quantity');
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
            if (Schema::hasColumn($this->tableName, 'classification')) {
                $table->dropColumn('classification');
            }
        });
    }
}
