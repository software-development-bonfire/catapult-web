<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductModifierDetailBidToOrderTakingItemSetupModifierDetail extends Migration
{
    protected $tableName = 'cdis_order_taking_item_setup_modifier_detail';
 
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'product_modifier_detail_bid')) {
                    $table->unsignedBigInteger('product_modifier_detail_bid')->after('head_bid');
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
                if (Schema::hasColumn($this->tableName, 'product_modifier_detail_bid')) {
                    $table->dropColumn('product_modifier_detail_bid');
                }
            });
        }
    }
}
