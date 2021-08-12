<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisKitchenItemSetupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_kitchen_item_setup', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->string('code', 45);
            $table->unsignedBigInteger('branch_bid');
            $table->unsignedBigInteger('device_type_bid');
            $table->tinyInteger('device_type');
            $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE);
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
        Schema::dropIfExists('cdis_kitchen_item_setup');
    }
}
