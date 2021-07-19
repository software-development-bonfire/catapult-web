<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_product', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->string('item_code', 128);
            $table->unsignedBigInteger('category_bid');
            $table->unsignedBigInteger('brand_bid');
            $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE);
            $table->tinyInteger('is_sell_item')->default(1);
            $table->tinyInteger('is_inventory_item')->default(1);
            $table->tinyInteger('is_finished_good')->default(0);
            $table->tinyInteger('tax_code')->default(\App\Enums\CDIS\TaxCode::VATABLE);
            $table->tinyInteger('is_senior_item')->default(0);
            $table->tinyInteger('is_pwd_item')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_product');
    }
}
