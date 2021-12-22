<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisPaymentMethodSettingsDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_payment_method_settings_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->string('field_name', 128);
            $table->string('field_value', 128)->nullable();
            $table->tinyInteger('is_required');
        });

        Schema::table('cdis_payment_method_settings_detail', function (Blueprint $table) {
            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_payment_method_settings')
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
        Schema::dropIfExists('cdis_payment_method_settings_detail');
    }
}
