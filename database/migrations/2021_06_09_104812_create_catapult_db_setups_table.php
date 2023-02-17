<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCatapultDbSetupsTable.
 */
class CreateCatapultDbSetupsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('catapult_db_setups', function(Blueprint $table) {
            $table->bigIncrements('id');
			$table->unsignedBigInteger('bid')->index()->unique();
			$table->string('name', 45);
			$table->string('host', 45);
			$table->string('port', 45);
			$table->string('db_name', 45);
			$table->string('username', 45);
			$table->string('password', 128);
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
		Schema::drop('catapult_db_setups');
	}
}
