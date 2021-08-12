<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisPriceOverrideTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_price_override', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_detail_bid');
            $table->unsignedBigInteger('transaction_product_bid');
            $table->unsignedBigInteger('product_bid')->nullable();
            $table->string('product_name', 512);
            $table->string('product_description', 512)->nullable();
            $table->string('product_code', 128)->nullable();
            $table->decimal('old_price', 23, 6)->default(0.000000);
            $table->decimal('new_price', 23, 6)->default(0.000000);
            $table->decimal('quantity', 23, 6)->default(0.000000);
            $table->string('approved_by', 45)->nullable();
            $table->dateTime('approved_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_price_override');
    }
}
