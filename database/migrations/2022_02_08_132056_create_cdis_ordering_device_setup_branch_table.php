<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisOrderingDeviceSetupBranchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_ordering_device_setup_branch', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->unsignedBigInteger('branch_bid');

            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_ordering_device_setup')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('branch_bid')
                ->references('bid')
                ->on('cdis_branch')
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
        Schema::dropIfExists('cdis_ordering_device_setup_branch');
    }
}
