<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFieldMappingPresetTable.
 */
class CreateFieldMappingPresetTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	    Schema::create('field_mapping_preset', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
			$table->tinyInteger('type');
            $table->string('data_entry', 45);
            $table->string('preset_name', 45);
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
		Schema::drop('field_mapping_preset');
	}
}
