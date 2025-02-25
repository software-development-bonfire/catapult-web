<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIsPrintStickerInCdisProductUomPackagingTable extends Migration
{
    protected $tableName = 'cdis_product_uom_packaging';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'is_print_sticker')) {
                    $table->boolean('is_print_sticker')->default(0)->after('has_expiry');
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
                if (Schema::hasColumn($this->tableName, 'is_print_sticker')) {
                    $table->dropColumn('is_print_sticker');
                }
            });
        }
    }
}
