<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisTerminalTransactionProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_terminal_transaction_product', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_detail_bid');
            $table->unsignedBigInteger('product_bid')->nullable();
            $table->string('name', 512);
            $table->string('description', 512)->nullable();
            $table->string('long_description', 1024)->nullable();
            $table->string('menu_code', 128)->nullable();
            $table->unsignedBigInteger('category_bid')->nullable();
            $table->string('category_name', 128)->nullable();
            $table->decimal('quantity', 23, 6)->default(0.000000);
            $table->decimal('tax_percentage', 23, 6)->default(0.000000);
            $table->unsignedBigInteger('order_type_id')->nullable();
            $table->string('order_type_name', 64)->nullable();
            $table->tinyInteger('is_free')->default(0);
            $table->tinyInteger('is_vatable')->default(1);
            $table->decimal('original_price', 23, 6)->default(0.000000);
            $table->decimal('price', 23, 6)->default(0.000000);
            $table->decimal('total_addon', 23, 6)->default(0.000000);
            $table->decimal('total_amount', 23, 6)->default(0.000000);
            $table->decimal('vatable_sales', 23, 6)->default(0.000000);
            $table->decimal('zero_rated_sales', 23, 6)->default(0.000000);
            $table->decimal('amount_discount', 23, 6)->default(0.000000);
            $table->decimal('tax', 23, 6)->default(0.000000);
            $table->decimal('vat_deduct', 23, 6)->default(0.000000);
            $table->decimal('vat_exempt', 23, 6)->default(0.000000);
            $table->tinyInteger('split_number')->nullable();
            $table->string('remarks', 512)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('transaction_detail_bid', 'product_transaction_detail_bid_foreign')
                ->references('bid')
                ->on('cdis_terminal_transaction_detail')
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
        Schema::dropIfExists('cdis_terminal_transaction_product');
    }
}
