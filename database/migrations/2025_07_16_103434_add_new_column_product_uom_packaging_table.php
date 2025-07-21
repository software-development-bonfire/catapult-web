<?php

use App\Enums\CDIS\ServiceType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewColumnProductUomPackagingTable extends Migration
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
                if (! Schema::hasColumn($this->tableName, 'is_solo_parent_item')) {
                    $table->tinyInteger('is_solo_parent_item')->default(0)->after('is_pwd_item');
                }
                if (! Schema::hasColumn($this->tableName, 'is_diplomat_item')) {
                    $table->tinyInteger('is_diplomat_item')->default(0)->after('is_solo_parent_item');
                }
                if (! Schema::hasColumn($this->tableName, 'is_athlete_item')) {
                    $table->tinyInteger('is_athlete_item')->default(0)->after('is_diplomat_item');
                }
                if (! Schema::hasColumn($this->tableName, 'service_type')) {
                    $table->tinyInteger('service_type')->default(ServiceType::FOOD_AND_BEVERAGES)->after('trade_type');
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
                if (Schema::hasColumn($this->tableName, $this->tableName)) {
                    $table->dropColumn($this->tableName);
                }
                if (Schema::hasColumn($this->tableName, 'is_diplomat_item')) {
                    $table->dropColumn('is_diplomat_item');
                }
                if (Schema::hasColumn($this->tableName, 'is_athlete_item')) {
                    $table->dropColumn('is_athlete_item');
                }
                if (Schema::hasColumn($this->tableName, 'service_type')) {
                    $table->dropColumn('service_type');
                }
            });
        }
    }
}
