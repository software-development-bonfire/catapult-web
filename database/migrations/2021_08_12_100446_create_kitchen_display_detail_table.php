<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKitchenDisplayDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kitchen_display_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->unsignedBigInteger('transaction_product_bid');
            $table->decimal('remaining_quantity', 23, 6)->default(0.000000);
            $table->unsignedBigInteger('kitchen_station_bid')->nullable();
            $table->tinyInteger('status');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('head_bid')
                ->references('bid')
                ->on('kitchen_display')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('transaction_product_bid')
                ->references('bid')
                ->on('cdis_terminal_transaction_product')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('kitchen_station_bid')
                ->references('bid')
                ->on('cdis_kitchen_station')
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
        Schema::dropIfExists('kitchen_display_detail');
    }
}
