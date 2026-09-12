<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Enums\KDS\OrderType;
use App\Enums\UsageType;

class CreateStoreOrderDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_order_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index();
            $table->unsignedBigInteger('store_order_bid');
            $table->unsignedBigInteger('product_uom_bid')->nullable();
            $table->string('menu_code')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('long_description')->nullable();
            $table->decimal('quantity', 23, 6)->default(0.000000);
            $table->tinyInteger('usage_type')->default(UsageType::PRODUCT);
            $table->tinyInteger('is_addon')->default(0);
            $table->tinyInteger('order_type_id')->default(OrderType::DINE_IN);
            $table->longText('special_request')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_order_detail');
    }
}
