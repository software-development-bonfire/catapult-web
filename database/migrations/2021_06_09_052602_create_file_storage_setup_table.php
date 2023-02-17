<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFileStorageSetupTable.
 */
class CreateFileStorageSetupTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('file_storage_setup', function(Blueprint $table) {
            $table->bigIncrements('id');
			$table->unsignedBigInteger('bid')->index()->unique();
			$table->string('name', 45);
            $table->string('local_path', 512);
            $table->string('remote_path', 512);
			$table->string('server', 128);
			$table->string('host', 128);
			$table->tinyInteger('port')->nullable();
			$table->string('username', 45)->nullable();
			$table->string('password', 128)->nullable();
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
		Schema::drop('file_storage_setup');
	}
}
