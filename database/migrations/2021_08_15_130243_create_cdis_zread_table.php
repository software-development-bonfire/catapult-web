<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCDISZreadsTable.
 */
class CreateCDISZreadTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cdis_zread', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('terminal_bid');

            // Z-Read details
            $table->string('day_end_report_number', 48);
            $table->string('administrator', 128);
            $table->string('cashier', 128);
            $table->date('log_date');
            $table->dateTime('date_time');
            $table->integer('guest_count')->default(0);

            // Sales Summary
            $table->decimal('gross_sales_amount', 23, 6)->default(0.000000);
            $table->integer('transaction_count')->default(0);
            $table->integer('mandated_discount_transaction_count')->default(0);
            $table->integer('total_regular_discount_count')->default(0);
            $table->decimal('total_regular_discount_amount', 23, 6)->default(0.000000);
            $table->integer('senior_transaction_count')->default(0);
            $table->decimal('senior_discount_amount', 23, 6)->default(0.000000);
            $table->decimal('sc_vat_deduction_amount', 23, 6)->default(0.000000);
            $table->integer('pwd_transaction_count')->default(0);
            $table->decimal('pwd_transaction_amount', 23, 6)->default(0.000000);
            $table->decimal('pwd_vat_deduction', 23, 6)->default(0.000000);
            $table->integer('diplomat_transaction_count')->default(0);
            $table->decimal('diplomat_vat_deduction', 23, 6)->default(0.000000);
            $table->decimal('vatable_sales', 23, 6)->default(0.000000);
            $table->decimal('vat_amount', 23, 6)->default(0.000000);
            $table->decimal('vat_exempt_sales', 23, 6)->default(0.000000);
            $table->decimal('less_mandated_vat_and_discount', 23, 6)->default(0.000000);
            $table->decimal('net_of_vat_exempt', 23, 6)->default(0.000000);
            $table->decimal('zero_rated_sales', 23, 6)->default(0.000000);
            $table->decimal('non_vat_sales', 23, 6)->default(0.000000);
            $table->decimal('subtotal', 23, 6)->default(0.000000);
            $table->decimal('service_charge', 23, 6)->default(0.000000);
            $table->decimal('net_total', 23, 6)->default(0.000000);

            // Transaction Details
            $table->bigInteger('beginning_or');
            $table->bigInteger('ending_or');
            $table->integer('no_sales_transaction_count')->default(0);
            $table->integer('void_transactions_count')->default(0);
            $table->decimal('void_transactions_amount', 23, 6)->default(0.000000);
            $table->integer('void_items_count')->default(0);
            $table->decimal('void_items_amount', 23, 6)->default(0.000000);
            $table->integer('refunds_count')->default(0);
            $table->decimal('refunds_amount', 23, 6)->default(0.000000);

            // Tender Summary
            $table->integer('total_tenders_count')->default(0);
            $table->decimal('total_tenders_amount', 23, 6)->default(0.000000);
            $table->decimal('add_initial_cash_amount', 23, 6)->default(0.000000);
            $table->decimal('less_withdrawals_amount', 23, 6)->default(0.000000);
            $table->decimal('add_cash_returns_amount', 23, 6)->default(0.000000);
            $table->decimal('total_drawer_amount', 23, 6)->default(0.000000);

            // Cash Breakdown
            $table->decimal('total_cash_breakdown_amount', 23, 6)->default(0.000000);
            $table->decimal('short_over_amount', 23, 6)->default(0.000000);

            // Cashier Sales Summary
            $table->decimal('total_cashier_sales_amount', 23, 6)->default(0.000000);

            // Accumulated Balance
            $table->decimal('beginning_balance', 23, 6)->default(0.000000);
            $table->decimal('ending_balance', 23, 6)->default(0.000000);

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
		Schema::drop('cdis_zread');
	}
}
