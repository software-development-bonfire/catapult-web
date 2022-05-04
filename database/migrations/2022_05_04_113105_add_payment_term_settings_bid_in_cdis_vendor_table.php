<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTermSettingsBidInCdisVendorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_vendor', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_term_settings_bid')->after('payment_term_days')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_vendor', function (Blueprint $table) {
            $table->dropColumn('payment_term_settings_bid');
        });
    }
}
