<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFieldMappingDetailsTable.
 */
class CreateFieldMappingDetailsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('field_mapping_details', function(Blueprint $table) {
            $table->bigIncrements('id');
			$table->bigInteger('bid');
			$table->bigInteger('field_mapping_bid');
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
		Schema::drop('field_mapping_details');
	}
}
