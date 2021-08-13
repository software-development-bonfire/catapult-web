<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisTerminalTransactionAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_terminal_transaction_addon', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_product_bid');
            $table->unsignedBigInteger('product_bid')->nullable();
            $table->string('name', 512);
            $table->decimal('quantity', 23, 6)->default(0.000000);
            $table->decimal('original_price', 23, 6)->default(0.000000);
            $table->decimal('price', 23, 6)->default(0.000000);
            $table->decimal('total_amount', 23, 6)->default(0.000000);
            $table->string('remarks', 512)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('transaction_product_bid', 'addon_transaction_product_bid_foreign')
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
        Schema::dropIfExists('cdis_terminal_transaction_addon');
    }
}
