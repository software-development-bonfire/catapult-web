<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderTakingItemSetupBarcodeTable extends Migration
{
    protected $tableName = 'cdis_order_taking_item_setup_barcode';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index()->unique();
                $table->unsignedBigInteger('head_bid')->index('otisb_otisd_bid_index');
                $table->unsignedBigInteger('product_uom_packaging_bid')->index('otisb_pup_bid_index');
                $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE);

                $table->foreign('head_bid', 'otisb_otisd_bid_foreign')
                    ->references('bid')
                    ->on('cdis_order_taking_item_setup_detail')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $table->foreign('product_uom_packaging_bid', 'otisb_pup_bid_foreign')
                    ->references('bid')
                    ->on('cdis_product_uom_packaging')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
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
        Schema::dropIfExists($this->tableName);
    }
}
