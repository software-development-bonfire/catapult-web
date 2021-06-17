<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFieldMappingsTable.
 */
class CreateFieldMappingsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('field_mappings', function(Blueprint $table) {
            $table->bigIncrements('id');
			$table->bigInteger('bid');
			$table->tinyInteger('type');
			$table->string('api_endpoint', 45);
			$table->string('api_version_name', 128);
			$table->tinyInteger('status');
			$table->bigInteger('created_by');
			$table->bigInteger('updated_by')->nullable();
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
		Schema::drop('field_mappings');
	}
}
