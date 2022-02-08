<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisProductPricingTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_product_pricing_type', function (Blueprint $table) {
            $table->tinyInteger('display_priority')->after('alias');
            $table->tinyInteger('status')->after('display_priority')->default(\App\Enums\Status::ACTIVE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_product_pricing_type', function (Blueprint $table) {
            $table->dropColumn('display_priority');
            $table->dropColumn('status');
        });
    }
}
