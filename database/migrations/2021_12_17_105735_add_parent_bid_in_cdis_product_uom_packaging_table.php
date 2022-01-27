<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddParentBidInCdisProductUomPackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_bid')->after('uom_bid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->dropColumn('parent_bid');
        });
    }
}
