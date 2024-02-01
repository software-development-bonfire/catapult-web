<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePOSTerminalTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('pos_terminal_transactions')) {
            Schema::create('pos_terminal_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index();
                $table->unsignedBigInteger('terminal_bid');
                $table->unsignedBigInteger('branch_bid');
                $table->string('transaction_id');
                $table->timestamp('log_date')->nullable();
                $table->string('or_number');
                $table->tinyInteger('split_number');
                $table->tinyInteger('is_first_transaction');
                $table->tinyInteger('type');
                $table->tinyInteger('status');
                $table->decimal('gross_sales', 23, 6)->default(0.000000);
                $table->decimal('net_sales', 23, 6)->default(0.000000);
                $table->decimal('total_quantity', 23, 6)->default(0.000000);
                $table->decimal('total_free_items_amount', 23, 6)->default(0.000000);
                $table->decimal('total_local_tax_amount', 23, 6)->default(0.000000);
                $table->decimal('total_tax_amount', 23, 6)->default(0.000000);
                $table->decimal('total_discount_amount', 23, 6)->default(0.000000);
                $table->decimal('total_vat_deduct_amount', 23, 6)->default(0.000000);
                $table->decimal('total_vat_exempt_amount', 23, 6)->default(0.000000);
                $table->decimal('total_vatable_sales', 23, 6)->default(0.000000);
                $table->decimal('total_zero_rated_sales', 23, 6)->default(0.000000);
                $table->decimal('total_tender', 23, 6)->default(0.000000);
                $table->decimal('eligible_amount_to_earn_points', 23, 6)->default(0.000000);
                $table->decimal('guest_count', 23, 6)->default(0.000000);
                $table->decimal('service_charge', 23, 6)->default(0.000000);
                $table->string('order_number');
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
                $table->tinyInteger('is_reset');
                $table->string('receipt');
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
        Schema::dropIfExists('pos_terminal_transactions');
    }
}
