<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Class CreateFieldMappingTable.
 */
class CreateFieldMappingTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('field_mapping', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('file_storage_setup_bid');
            $table->unsignedBigInteger('catapult_db_setup_bid');
            $table->unsignedBigInteger('api_setup_bid');
            $table->string('name');
            $table->integer('type');
            $table->tinyInteger('status');
            $table->string('data_entry', 45)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('file_storage_setup_bid')
                ->references('bid')
                ->on('file_storage_setup')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('catapult_db_setup_bid')
                ->references('bid')
                ->on('catapult_db_setups')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('api_setup_bid')
                ->references('bid')
                ->on('api_setups')
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
		Schema::drop('field_mapping');
	}
}
