<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateErrorLogsTable.
 */
class CreateErrorLogsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('error_logs', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->string('pos_entry');
            $table->string('filename');
            $table->string('path')->nullable();
            $table->string('status');
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
		Schema::drop('error_logs');
	}
}
