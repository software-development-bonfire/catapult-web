<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateOrderTakingItemSetupDeviceSetupTable extends Migration
{
    protected $tableName = 'cdis_order_taking_item_setup_device_setup';
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
                $table->unsignedBigInteger('order_taking_item_setup_detail_bid')->index('otisds_otisd_bid_index');
                $table->unsignedBigInteger('ordering_device_setup_bid')->index('otisds_ods_bid_index');
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

                $table->foreign('order_taking_item_setup_detail_bid', 'otisds_otisd_bid_foreign')
                    ->references('bid')
                    ->on('cdis_order_taking_item_setup_detail')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $table->foreign('ordering_device_setup_bid', 'otisds_ods_bid_foreign')
                    ->references('bid')
                    ->on('cdis_ordering_device_setup')
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
