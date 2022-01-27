<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisDiscountSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_discount_settings', function (Blueprint $table) {
            $table->decimal('maximum_amount', 23, 6)->after('discount_amount');
            $table->decimal('minimum_purchase', 23, 6)->after('maximum_amount');
            $table->decimal('ceiling_amount', 23, 6)->after('minimum_purchase');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_discount_settings', function (Blueprint $table) {
            $table->dropColumn('maximum_amount');
            $table->dropColumn('minimum_purchase');
            $table->dropColumn('ceiling_amount');
        });
    }
}
