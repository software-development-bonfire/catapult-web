<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateStationOtsTerminalTransactionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('station_ots_terminal_transaction_products')) {
            Schema::create('station_ots_terminal_transaction_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index();
                $table->unsignedBigInteger('cart_bid')->nullable();
                $table->string('terminal_transaction_bid')->nullable();
                $table->tinyInteger('usage_type')->nullable();
                $table->tinyInteger('product_type')->default(0);
                $table->unsignedBigInteger('product_bid')->nullable();
                $table->string('parent_bid')->nullable();
                $table->string('name')->nullable();
                $table->string('description')->nullable();
                $table->string('long_description')->nullable();
                $table->string('menu_code')->nullable();
                $table->unsignedBigInteger('category_bid')->nullable();
                $table->decimal('quantity', 23, 6)->default(0.000000);
                $table->decimal('tax_percentage', 23, 6)->default(0.000000);
                $table->unsignedBigInteger('order_type_id')->nullable();
                $table->string('order_type_name')->nullable();
                $table->tinyInteger('is_free')->default(0);
                $table->tinyInteger('tax_code')->nullable();
                $table->decimal('original_price', 23, 6)->default(0.000000);
                $table->decimal('price', 23, 6)->default(0.000000);
                $table->decimal('individual_total_amount', 23, 6)->default(0.000000);
                $table->decimal('individual_total_discount', 23, 6)->default(0.000000);
                $table->decimal('total_addon_amount', 23, 6)->default(0.000000);
                $table->decimal('entire_discount', 23, 6)->default(0.000000);
                $table->decimal('entire_amount', 23, 6)->default(0.000000);
                $table->decimal('vatable_sales', 23, 6)->default(0.000000);
                $table->decimal('zero_rated_sales', 23, 6)->default(0.000000);
                $table->decimal('tax', 23, 6)->default(0.000000);
                $table->decimal('vat_deduct', 23, 6)->default(0.000000);
                $table->decimal('vat_exempt', 23, 6)->default(0.000000);
                $table->decimal('sub_total', 23, 6)->default(0.000000);
                $table->decimal('gross_total', 23, 6)->default(0.000000);
                $table->decimal('net_total', 23, 6)->default(0.000000);
                $table->timestamp('transaction_date')->nullable();
                $table->timestamp('log_date')->nullable();
                $table->integer('parent_id')->nullable();
                $table->tinyInteger('add_on')->nullable();
                $table->tinyInteger('take_home')->nullable();
                $table->string('remarks')->nullable();
                $table->text('special_request')->nullable();
                $table->unsignedBigInteger('supervisor_bid')->nullable();
                $table->string('supervisor_name')->nullable();
                $table->unsignedBigInteger('cashier_bid')->nullable();
                $table->string('cashier_name')->nullable();
                $table->unsignedBigInteger('discount_bid')->nullable();
                $table->string('discount_value')->nullable();
                $table->tinyInteger('status')->default(0);
                $table->tinyInteger('is_reset')->default(0);
                $table->timestamp('created_at')->nullable();
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
        Schema::dropIfExists('station_ots_terminal_transaction_products');
    }
}
