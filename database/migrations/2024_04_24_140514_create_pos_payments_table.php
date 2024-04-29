<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePosPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('pos_payments')) {
            Schema::create('pos_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index();
                $table->string('terminal_transaction_bid');
                $table->unsignedBigInteger('payment_method_bid');
                $table->string('title');
                $table->string('account_number')->nullable();
                $table->decimal('amount', 23, 6)->default(0.000000);
                $table->tinyInteger('status');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('log_date')->nullable();
                $table->text('remarks')->nullable();
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
        Schema::dropIfExists('pos_payments');
    }
}
