<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisBranchGroupTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_branch_group_tag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('branch_group_bid');
            $table->unsignedBigInteger('branch_bid');
        });

        Schema::table('cdis_branch_group_tag', function (Blueprint $table) {
            $table->foreign('branch_group_bid')
                ->references('bid')
                ->on('cdis_branch_group')
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
        Schema::dropIfExists('cdis_branch_group_tag');
    }
}
