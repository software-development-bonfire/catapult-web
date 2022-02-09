<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisProductModifierDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_product_modifier_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->unsignedBigInteger('product_uom_bid');
            $table->unsignedBigInteger('product_branch_price_bid');
            $table->decimal('quantity', 23, 6)->default(0.000000);

            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_product_modifier')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('product_uom_bid')
                ->references('bid')
                ->on('cdis_product_uom_packaging')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('product_branch_price_bid')
                ->references('bid')
                ->on('cdis_product_branch_price')
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
        Schema::dropIfExists('cdis_product_modifier_detail');
    }
}
