<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAvailableColumnInCdisPackagingVendorBranchCostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_packaging_vendor_branch_cost', function (Blueprint $table) {
            $table->tinyInteger('is_available')->after('price_to_branch_markup')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_packaging_vendor_branch_cost', function (Blueprint $table) {
            $table->dropColumn('is_available');
        });
    }
}
