<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKitchenDisplayMovementHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('kitchen_display_movement_history')) {
            return;
        }

        Schema::create('kitchen_display_movement_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('detail_bid');
            // Movement Details
            $table->integer('from_station_index')->nullable();
            $table->integer('to_station_index')->nullable();
            $table->integer('quantity_moved')->default(0);

            // Movement Type
            $table->enum('movement_type', ['FORWARD', 'BACKWARD', 'TO_RELEASING'])->default('FORWARD')->index();

            // Status Tracking
            $table->enum('status_before', ['ON_PROCESS', 'RELEASING', 'DONE', 'DELETED'])->default('ON_PROCESS');
            $table->enum('status_after', ['ON_PROCESS', 'RELEASING', 'DONE', 'DELETED'])->default('ON_PROCESS');

            // Metadata
            $table->timestamps();

            // Indices
            $table->index(['detail_bid', 'created_at']);
            $table->index('movement_type');
            $table->index('created_at');

            // Foreign Key
            $table->foreign('detail_bid')
                ->references('bid')
                ->on('kitchen_display_detail')
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
        Schema::dropIfExists('kitchen_display_movement_history');
    }
}
