<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisTerminalTransactionDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_terminal_transaction_discount', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_product_bid');
            $table->unsignedBigInteger('discount_bid')->nullable();
            $table->string('title', 45);
            $table->decimal('total', 23, 6)->default(0.000000);
            $table->decimal('amount_discount', 23, 6)->default(0.000000);
            $table->decimal('vat_deduct', 23, 6)->default(0.000000);
            $table->decimal('vat_exempt', 23, 6)->default(0.000000);
            $table->tinyInteger('mandated')->default(0);
            $table->string('remarks', 512)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('transaction_product_bid', 'discount_transaction_product_bid_foreign')
                ->references('bid')
                ->on('cdis_terminal_transaction_product')
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
        Schema::dropIfExists('cdis_terminal_transaction_discount');
    }
}
