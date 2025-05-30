<?php

use App\Traits\MigrationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePOSTerminalTransactionProductsTable extends Migration
{
    use MigrationTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('pos_terminal_transaction_products')) {
            Schema::create('pos_terminal_transaction_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index();
                $table->unsignedBigInteger('cart_bid');
                $table->string('terminal_transaction_bid');
                $table->tinyInteger('usage_type');
                $table->unsignedBigInteger('product_bid');
                $table->string('name');
                $table->string('description');
                $table->string('long_description');
                $table->string('menu_code');
                $table->unsignedBigInteger('category_bid');
                $table->decimal('quantity', 23, 6)->default(0.000000);
                $table->decimal('tax_percentage', 23, 6)->default(0.000000);
                $table->unsignedBigInteger('order_type_id');
                $table->string('order_type_name')->nullable();
                $table->tinyInteger('is_free');
                $table->tinyInteger('tax_code');
                $table->decimal('original_price', 23, 6)->default(0.000000);
                $table->decimal('price', 23, 6)->default(0.000000);
                $table->decimal('individual_total_amount', 23, 6)->default(0.000000);
                $table->decimal('individual_total_discount', 23, 6)->default(0.000000);
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
                $table->integer('parent_id');
                $table->tinyInteger('add_on');
                $table->tinyInteger('take_home');
                $table->string('remarks')->nullable();
                $table->unsignedBigInteger('supervisor_bid')->nullable();
                $table->string('supervisor_name')->nullable();
                $table->unsignedBigInteger('cashier_bid')->nullable();
                $table->string('cashier_name')->nullable();
                $table->unsignedBigInteger('discount_bid');
                $table->string('discount_value');
                $table->tinyInteger('is_reset');
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
        Schema::dropIfExists('pos_terminal_transaction_products');
    }
}
