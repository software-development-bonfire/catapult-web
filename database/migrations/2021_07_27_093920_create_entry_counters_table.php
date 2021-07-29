<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateEntryCountersTable.
 */
class CreateEntryCountersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('entry_counters', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid');
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
		Schema::drop('entry_counters');
	}
}
