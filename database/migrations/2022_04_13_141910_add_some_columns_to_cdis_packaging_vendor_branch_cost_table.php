<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsToCdisPackagingVendorBranchCostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_packaging_vendor_branch_cost', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('is_available');
            $table->decimal('discount_1', 23, 6)->after('discount_type');
            $table->decimal('discount_2', 23, 6)->after('discount_1');
            $table->decimal('discount_3', 23, 6)->after('discount_2');
            $table->decimal('discount_4', 23, 6)->after('discount_3');
            $table->decimal('adjustment', 23, 6)->after('discount_4');
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
            $table->dropColumn('discount_type');
            $table->dropColumn('discount_1');
            $table->dropColumn('discount_2');
            $table->dropColumn('discount_3');
            $table->dropColumn('discount_4');
            $table->dropColumn('adjustment');
        });
    }
}
