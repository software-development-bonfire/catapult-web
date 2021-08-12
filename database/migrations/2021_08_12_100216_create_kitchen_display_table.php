<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKitchenDisplayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kitchen_display', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_detail_bid');
            $table->dateTime('completed_at')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('transaction_detail_bid')
                ->references('bid')
                ->on('cdis_terminal_transaction_detail')
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
        Schema::dropIfExists('kitchen_display');
    }
}
