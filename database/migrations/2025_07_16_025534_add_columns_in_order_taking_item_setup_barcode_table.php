<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddColumnsInOrderTakingItemSetupBarcodeTable extends Migration
{
    protected $tableName = 'cdis_order_taking_item_setup_barcode';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'created_at')) {
                    $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                }
                if (! Schema::hasColumn($this->tableName, 'updated_at')) {
                    $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->after('created_at');;
                }
                if (! Schema::hasColumn($this->tableName, 'deleted_at')) {
                    $table->softDeletes();
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
                if (Schema::hasColumn($this->tableName, 'created_at')) {
                    $table->dropColumn('created_at');
                }
                if (Schema::hasColumn($this->tableName, 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
                if (Schema::hasColumn($this->tableName, 'deleted_at')) {
                    $table->dropColumn('deleted_at');
                }
            });
        }
    }
}
