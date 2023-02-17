<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisDisplayPaymentMethodDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_display_payment_method_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->unsignedBigInteger('payment_method_settings_bid');
            $table->string('name', 64);
            $table->tinyInteger('display_priority');
            $table->tinyInteger('status')->default(\App\Enums\Status::ACTIVE);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });

        Schema::table('cdis_display_payment_method_detail', function (Blueprint $table) {
            $table->foreign('payment_method_settings_bid', 'display_payment_method_settings_bid_foreign')
                ->references('bid')
                ->on('cdis_payment_method_settings')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('head_bid')
                ->references('bid')
                ->on('cdis_display_payment_method')
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
        Schema::dropIfExists('cdis_display_payment_method_detail');
    }
}
