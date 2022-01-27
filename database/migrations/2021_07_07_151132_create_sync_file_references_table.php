<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateSyncFileReferencesTable.
 */
class CreateSyncFileReferencesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sync_file_references', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->tinyInteger('storage_type');
			$table->string('filename', 128);
			$table->string('extension', 45);
			$table->string('path', 512);
			$table->dateTime('last_modified');
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
		Schema::drop('sync_file_references');
	}
}
