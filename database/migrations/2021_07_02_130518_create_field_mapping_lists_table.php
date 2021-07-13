<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Class CreateFieldMappingListsTable.
 */
class CreateFieldMappingListsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('field_mapping_lists', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid');
            $table->unsignedBigInteger('field_mapping_bid')->nullable();
            $table->unsignedBigInteger('remote_setup_bid');
            $table->unsignedBigInteger('catapult_db_setup_bid');
            $table->unsignedBigInteger('api_setup_bid');
            $table->string('name');
            $table->integer('type');
            $table->tinyInteger('status');
			$table->string('api_endpoint', 45)->nullable();
			$table->string('api_version_name', 45)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
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
		Schema::drop('field_mapping_lists');
	}
}
