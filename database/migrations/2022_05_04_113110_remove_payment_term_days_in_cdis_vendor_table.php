<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePaymentTermDaysInCdisVendorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('cdis_vendor', 'payment_term_days')) {
            Schema::table('cdis_vendor', function (Blueprint $table) {
                $table->dropColumn('payment_term_days');
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
        if (! Schema::hasColumn('cdis_vendor', 'payment_term_days')) {
            Schema::table('cdis_vendor', function (Blueprint $table) {
                $table->smallInteger('payment_term_days');
            });
        }
    }
}
