<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisProductAddonDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_product_addon_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->unsignedBigInteger('product_uom_bid');
        });

        Schema::table('cdis_product_addon_detail', function (Blueprint $table) {
            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_product_addon')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('product_uom_bid')
                ->references('bid')
                ->on('cdis_product_uom_packaging')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_product_addon_detail');
    }
}
