<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisSyncTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_sync', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index();
            $table->unsignedBigInteger('branch_bid');
            $table->unsignedBigInteger('table_bid');
            $table->string('table_name', 64);
            $table->tinyInteger('level');
            $table->string('group', 64)->nullable();
            $table->string('code', 45);
            $table->string('action', 32);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_sync');
    }
}
