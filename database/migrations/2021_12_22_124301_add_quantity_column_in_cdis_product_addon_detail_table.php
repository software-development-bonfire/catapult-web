<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQuantityColumnInCdisProductAddonDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product_addon_detail', function (Blueprint $table) {
            $table->decimal('quantity', 23, 6)->default(0.000000)->after('product_uom_bid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product_addon_detail', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
}
