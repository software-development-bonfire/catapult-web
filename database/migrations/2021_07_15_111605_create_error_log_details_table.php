<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateErrorLogDetailsTable.
 */
class CreateErrorLogDetailsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('error_log_details', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid');
            $table->unsignedBigInteger('error_log_bid');
            $table->string('sheet');
            $table->string('error_type');
            $table->text('description');
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
		Schema::drop('error_log_details');
	}
}
