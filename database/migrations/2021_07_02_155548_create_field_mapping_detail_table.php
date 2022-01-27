<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Class CreateFieldMappingDetailTable.
 */
class CreateFieldMappingDetailTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('field_mapping_detail', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('field_mapping_bid');
            $table->tinyInteger('required');
            $table->string('field', 512);
            $table->string('description', 512)->nullable();
            $table->string('mapping_type', 45);
            $table->string('file_name', 45)->nullable();
            $table->string('default_value', 1024)->nullable();
            $table->string('column_name', 45)->nullable();
            $table->string('reference_column_name', 45)->nullable();
            $table->string('head_reference', 512)->nullable();
            $table->timestamps();

            $table->foreign('field_mapping_bid')
                ->references('bid')
                ->on('field_mapping')
                ->onUpdate('restrict')
                ->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('field_mapping_detail');
	}
}
