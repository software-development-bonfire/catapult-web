<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisCostAndPriceChangeDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_cost_and_price_change_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->unsignedBigInteger('product_uom_bid');
            $table->unsignedBigInteger('branch_bid')->nullable();
            $table->tinyInteger('pricing_type')->default(\App\Enums\CDIS\CostAndPriceChangePricingType::PRICE);
            $table->unsignedBigInteger('product_pricing_type_bid')->nullable();
            $table->unsignedBigInteger('pricing_head_bid')->nullable();
            $table->decimal('old_value', 23, 6)->nullable();
            $table->decimal('new_value', 23, 6)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_cost_and_price_change')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('product_uom_bid')
                ->references('bid')
                ->onUpdate('restrict')
                ->on('cdis_product_uom_packaging')
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
        Schema::dropIfExists('cdis_cost_and_price_change_detail');
    }
}
