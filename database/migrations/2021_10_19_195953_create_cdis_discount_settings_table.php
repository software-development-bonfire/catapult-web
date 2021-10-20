<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisDiscountSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('cdis_discount_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->string('code', 45);
            $table->string('description', 128);
            $table->tinyInteger('method');
            $table->tinyInteger('discount_type');
            $table->decimal('discount_amount', 23, 6)->default(0.000000);
            $table->integer('receipt_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
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
        Schema::dropIfExists('cdis_discount_settings');
    }
}
