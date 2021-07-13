<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Class CreateDataMappingsTable.
 */
class CreateDataMappingsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('data_mappings', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid');
            $table->unsignedBigInteger('field_mapping_list_bid');
            $table->tinyInteger('required');
            $table->string('field', 45);
            $table->string('description', 128)->nullable();
            $table->string('mapping_type', 45);
            $table->string('file_name', 45)->nullable();
            $table->string('default_value', 1024)->nullable();
            $table->string('column_name', 45)->nullable();
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
		Schema::drop('data_mappings');
	}
}
