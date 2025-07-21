<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateOrderTakingItemSetupModifierTable extends Migration
{
    protected $tableName = 'cdis_order_taking_item_setup_modifier';
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
                $table->unsignedBigInteger('order_taking_item_setup_detail_bid')->index('otism_otismd_bid_index');
                $table->tinyInteger('type');
                $table->string('name', 64);
                $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE);
                $table->tinyInteger('mode');
                $table->decimal('mode_value', 23, 6)->default(0.000000);
                $table->tinyInteger('is_allow_repetition_of_order');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
                $table->softDeletes();

                $table->foreign('order_taking_item_setup_detail_bid', 'otism_otismd_bid_foreign')
                    ->references('bid')
                    ->on('cdis_order_taking_item_setup_detail')
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
