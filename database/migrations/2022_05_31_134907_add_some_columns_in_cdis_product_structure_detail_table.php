<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisProductStructureDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product_structure_detail', function (Blueprint $table) {
            $table->tinyInteger('is_display_structure')->after('quantity')->default(0);
            $table->tinyInteger('is_include_on_reports')->after('is_display_structure')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product_structure_detail', function (Blueprint $table) {
            $table->dropColumn('is_display_structure');
            $table->dropColumn('is_include_on_reports');
        });
    }
}
