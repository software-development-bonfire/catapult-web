<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisTerminalTransactionPaymentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_terminal_transaction_payment_method', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_detail_bid');
            $table->unsignedBigInteger('payment_method_bid')->nullable();
            $table->string('title', 64);
            $table->decimal('total', 23, 6)->default(0.000000);
            $table->string('account_number', 64)->nullable();
            $table->string('remarks', 512)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaction_detail_bid', 'payment_method_transaction_detail_bid_foreign')
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
        Schema::dropIfExists('cdis_terminal_transaction_payment_method');
    }
}
