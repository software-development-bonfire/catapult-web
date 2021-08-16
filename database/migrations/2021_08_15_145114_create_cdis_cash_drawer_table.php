<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCDISCashDrawersTable.
 */
class CreateCDISCashDrawerTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cdis_cash_drawer', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('terminal_bid');
            $table->unsignedBigInteger('cashier_bid')->nullable();
            $table->string('cashier_name', 64)->nullable();
            $table->decimal('amount', 23, 6)->default(0.000000);
            $table->dateTime('date');
            $table->unsignedBigInteger('approver_bid')->nullable();
            $table->string('approver_name', 64)->nullable();
            $table->dateTime('approved_date')->nullable();
            $table->tinyInteger('type')->default(0);
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
		Schema::drop('cdis_cash_drawer');
	}
}
