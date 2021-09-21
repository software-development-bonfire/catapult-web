<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisKitchenStationProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_kitchen_station_process', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->string('code', 45);
            $table->string('description', 128);
            $table->unsignedBigInteger('kitchen_station_bid_1')->nullable();
            $table->unsignedBigInteger('kitchen_station_bid_2')->nullable();
            $table->unsignedBigInteger('kitchen_station_bid_3')->nullable();
            $table->unsignedBigInteger('kitchen_station_bid_4')->nullable();
            $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('kitchen_station_bid_1')
                ->references('bid')
                ->on('cdis_kitchen_station')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('kitchen_station_bid_2')
                ->references('bid')
                ->on('cdis_kitchen_station')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('kitchen_station_bid_3')
                ->references('bid')
                ->on('cdis_kitchen_station')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('kitchen_station_bid_4')
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
        Schema::dropIfExists('cdis_kitchen_station_process');
    }
}
