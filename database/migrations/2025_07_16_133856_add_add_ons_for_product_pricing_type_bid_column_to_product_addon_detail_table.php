<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddOnsForProductPricingTypeBidColumnToProductAddonDetailTable extends Migration
{
    protected $tableName = 'cdis_product_addon_detail';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'add_ons_for_product_pricing_type_bid')) {
                    $table->unsignedBigInteger('add_ons_for_product_pricing_type_bid')->default(1)->after('head_bid');
                }
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
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (Schema::hasColumn($this->tableName, 'add_ons_for_product_pricing_type_bid')) {
                    $table->dropColumn('add_ons_for_product_pricing_type_bid');
                }
            });
        }
    }
}
