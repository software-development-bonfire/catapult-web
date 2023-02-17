<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsDefaultColumnInCdisPackagingVendorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_packaging_vendor', function (Blueprint $table) {
            $table->tinyInteger('is_default')->after('product_uom_bid')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_packaging_vendor', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}
