<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateStationOtsTerminalTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('station_ots_terminal_transactions')) {
            Schema::create('station_ots_terminal_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index();
                $table->unsignedBigInteger('terminal_bid')->nullable();
                $table->string('device_code')->nullable();
                $table->unsignedBigInteger('branch_bid')->nullable();
                $table->string('transaction_id')->nullable();
                $table->timestamp('log_date')->nullable();
                $table->string('or_number')->nullable();
                $table->tinyInteger('split_number')->nullable();
                $table->tinyInteger('is_first_transaction')->nullable();
                $table->tinyInteger('type')->nullable();
                $table->string('device_type')->nullable();
                $table->tinyInteger('device_mode')->nullable();
                $table->string('device_mode_label')->nullable();
                $table->tinyInteger('status')->nullable();
                $table->decimal('gross_sales', 23, 6)->default(0.000000);
                $table->decimal('net_sales', 23, 6)->default(0.000000);
                $table->decimal('total_quantity', 23, 6)->default(0.000000);
                $table->decimal('total_free_items_amount', 23, 6)->default(0.000000);
                $table->decimal('total_local_tax_amount', 23, 6)->default(0.000000);
                $table->decimal('total_tax_amount', 23, 6)->default(0.000000);
                $table->decimal('total_discount_amount', 23, 6)->default(0.000000);
                $table->decimal('total_delivery_fee', 23, 6)->default(0.000000);
                $table->decimal('total_vat_deduct_amount', 23, 6)->default(0.000000);
                $table->decimal('total_vat_exempt_amount', 23, 6)->default(0.000000);
                $table->decimal('total_vatable_sales', 23, 6)->default(0.000000);
                $table->decimal('total_zero_rated_sales', 23, 6)->default(0.000000);
                $table->decimal('total_tender', 23, 6)->default(0.000000);
                $table->decimal('eligible_amount_to_earn_points', 23, 6)->default(0.000000);
                $table->decimal('guest_count', 23, 6)->default(0.000000);
                $table->decimal('service_charge', 23, 6)->default(0.000000);
                $table->string('order_number')->nullable();
                $table->string('locator_number')->nullable();
                $table->string('order_type')->nullable();
                $table->string('order_schedule')->nullable();
                $table->string('billing_type')->nullable();
                $table->string('order_status')->default(0);
                $table->unsignedBigInteger('table_id')->nullable();
                $table->string('table_number')->nullable();
                $table->tinyInteger('customer_type')->nullable();
                $table->unsignedBigInteger('customer_bid')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_address')->nullable();
                $table->unsignedBigInteger('cashier_bid')->nullable();
                $table->string('cashier_name')->nullable();
                $table->string('remarks')->nullable();
                $table->decimal('change', 23, 6)->default(0.000000);
                $table->decimal('payment', 23, 6)->default(0.000000);
                $table->string('payment_status')->nullable();
                $table->tinyInteger('is_reset')->default(0);
                $table->string('receipt')->nullable();
                $table->string('order_slip_number')->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
                $table->timestamp('deleted_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('station_ots_terminal_transactions');
    }
}
