<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddModifierForProductPricingTypeBidColumnToProductModifierDetailTable extends Migration
{
    protected $tableName = 'cdis_product_modifier_detail';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'modifier_for_product_pricing_type_bid')) {
                    $table->unsignedBigInteger('modifier_for_product_pricing_type_bid')->default(1)->after('head_bid');
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
                if (Schema::hasColumn($this->tableName, 'modifier_for_product_pricing_type_bid')) {
                    $table->dropColumn('modifier_for_product_pricing_type_bid');
                }
            });
        }
    }
}
