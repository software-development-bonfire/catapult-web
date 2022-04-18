<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisCostAndPriceChangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_cost_and_price_change', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->string('code', '32')->default(0);
            $table->tinyInteger('pricing_type')->default(\App\Enums\CDIS\CostAndPriceChangePricingType::PRICE);
            $table->tinyInteger('type')->default(\App\Enums\CDIS\CostAndPriceChangeType::PERMANENT);
            $table->unsignedBigInteger('vendor_bid')->nullable();
            $table->unsignedBigInteger('category_bid');
            $table->dateTime('effective_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('expires_at')->nullable();
            $table->tinyInteger('status')->default(\App\Enums\CDIS\ApprovalStatus::PENDING);
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('vendor_bid')
                ->references('bid')
                ->on('cdis_vendor')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('category_bid')
                ->references('bid')
                ->on('cdis_product_category')
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
        Schema::dropIfExists('cdis_cost_and_price_change');
    }
}
