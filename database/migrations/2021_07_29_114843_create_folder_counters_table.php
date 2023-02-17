<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFolderCountersTable.
 */
class CreateFolderCountersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('folder_counters', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->integer('mapping_type');
            $table->integer('counter');
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
		Schema::drop('folder_counters');
	}
}
