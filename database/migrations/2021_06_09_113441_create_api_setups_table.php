<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateApiSetupsTable.
 */
class CreateApiSetupsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('api_setups', function(Blueprint $table) {
            $table->bigIncrements('id');
			$table->unsignedBigInteger('bid');
			$table->string('name', 45);
			$table->string('end_point', 128);
			$table->tinyInteger('status');
			$table->unsignedBigInteger('created_by');
			$table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('api_setups');
	}
}
