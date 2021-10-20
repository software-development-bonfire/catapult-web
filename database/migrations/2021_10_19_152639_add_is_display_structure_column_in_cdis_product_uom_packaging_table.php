<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsDisplayStructureColumnInCdisProductUomPackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->tinyInteger('is_display_structure')->default(0)->after('is_finished_good');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->dropColumn('is_display_structure');
        });
    }
}
