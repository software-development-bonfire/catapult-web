<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateRemoteSetupsTable.
 */
class CreateRemoteSetupsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('remote_setups', function(Blueprint $table) {
            $table->bigIncrements('id');
			$table->unsignedBigInteger('bid');
			$table->string('name', 45)->unique();
			$table->string('path', 45);
			$table->string('server', 45);
			$table->string('host', 45);
			$table->string('port', 45);
			$table->string('username', 45);
			$table->string('password', 128);
			$table->tinyInteger('status');
			$table->string('remarks', 128)->nullable();
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
		Schema::drop('remote_setups');
	}
}
