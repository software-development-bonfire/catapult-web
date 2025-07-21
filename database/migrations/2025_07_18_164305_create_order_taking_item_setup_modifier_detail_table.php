<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateOrderTakingItemSetupModifierDetailTable extends Migration
{
    protected $tableName = 'cdis_order_taking_item_setup_modifier_detail';
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
                $table->unsignedBigInteger('head_bid')->index('otismd_otism_bid_index');
                $table->unsignedBigInteger('product_uom_packaging_bid')->index('otismd_pup_bid_index');
                $table->string('display_name', 64);
                $table->tinyInteger('is_available');
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
                $table->softDeletes();

                $table->foreign('head_bid', 'otismd_otism_bid_foreign')
                    ->references('bid')
                    ->on('cdis_order_taking_item_setup_modifier')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $table->foreign('product_uom_packaging_bid', 'otismd_pup_bid_foreign')
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
