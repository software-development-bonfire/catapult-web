<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCDISPOSAuditTrailsTable.
 */
class CreateCDISPOSAuditTrailTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cdis_pos_audit_trail', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('log_id')->nullable();
            $table->unsignedBigInteger('terminal_bid');
            $table->dateTime('date');
            $table->string('application', 128)->nullable();
            $table->string('cashier', 128)->nullable();
            $table->string('supervisor', 128)->nullable();
            $table->string('job', 128)->nullable();
            $table->unsignedBigInteger('transaction_no')->default(0);
            $table->unsignedBigInteger('receipt_no')->default(0);
            $table->string('remarks', 512)->nullable();
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
		Schema::drop('cdis_pos_audit_trail');
	}
}
