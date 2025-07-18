<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateOrderTakingItemSetupDetailTable extends Migration
{
    protected $tableName = 'cdis_order_taking_item_setup_detail';
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
                $table->unsignedBigInteger('head_bid')->index();
                $table->unsignedBigInteger('display_category_bid')->index();
                $table->tinyInteger('display_priority');
                $table->string('display_name', 64)->nullable();
                $table->tinyInteger('is_available_whole_day')->default(\App\Enums\DisplayState::NO);
                $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
                $table->softDeletes();

                $table->foreign('head_bid')
                    ->references('bid')
                    ->on('cdis_order_taking_item_setup')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');

                $table->foreign('display_category_bid', 'otisd_dc_bid_foreign')
                    ->references('bid')
                    ->on('cdis_display_category')
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
