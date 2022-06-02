<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisProductUomPackagingTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_product_uom_packaging_tag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('product_uom_packaging_bid');
            $table->unsignedBigInteger('tag_bid');
        });
        
        Schema::table('cdis_product_uom_packaging_tag', function (Blueprint $table) {
            $table->foreign('product_uom_packaging_bid')
                ->references('bid')
                ->on('cdis_product_uom_packaging')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('tag_bid')
                ->references('bid')
                ->on('cdis_tags')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->dropUnique('cdis_product_uom_packaging_tag_bid_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_product_uom_packaging_tag');
    }
}
