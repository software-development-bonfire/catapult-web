<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveIsFinishedGoodColumnFromCdisProductTableToCdisProductUomPackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product', function (Blueprint $table) {
            $table->dropColumn('is_finished_good');
        });

        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->tinyInteger('is_finished_good')->default(0)->after('pack_content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product', function (Blueprint $table) {
            $table->tinyInteger('is_finished_good')->default(0)->after('status');
        });

        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->dropColumn('is_finished_good');
        });
    }
}
