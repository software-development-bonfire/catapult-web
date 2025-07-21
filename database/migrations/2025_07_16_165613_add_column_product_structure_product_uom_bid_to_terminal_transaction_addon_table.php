<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnProductStructureProductUomBidToTerminalTransactionAddonTable extends Migration
{
    protected $tableName = 'cdis_terminal_transaction_addon';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'product_structure_product_uom_bid')) {
                    $table->unsignedBigInteger('product_structure_product_uom_bid')->nullable()->after('usage_type');
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
                if (Schema::hasColumn($this->tableName, 'product_structure_product_uom_bid')) {
                    $table->dropColumn('product_structure_product_uom_bid');
                }
            });
        }
    }
}
