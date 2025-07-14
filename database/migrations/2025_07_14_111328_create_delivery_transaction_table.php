<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('delivery_transaction')) {
            Schema::create('delivery_transaction', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index();
                $table->unsignedBigInteger('pos_terminal_transaction_bid');
                $table->string('email_address')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('address')->nullable();
                $table->string('no_bldng_lot_street')->nullable();
                $table->string('delivery_instruction')->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
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
        Schema::dropIfExists('delivery_transaction');
    }
}
