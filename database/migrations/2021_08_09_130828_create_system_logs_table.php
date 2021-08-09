<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateSystemLogsTable.
 */
class CreateSystemLogsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('system_logs', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid');
            $table->string('initiator');
            $table->string('module_process');
            $table->string('action');
            $table->string('description');
            $table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('system_logs');
	}
}
