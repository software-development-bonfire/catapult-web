<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToCostAndPriceChangeDetailTable extends Migration
{
    protected $tableName = 'cdis_cost_and_price_change_detail';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'old_discount_type')) {
                    $table->tinyInteger('old_discount_type')->nullable()->after('new_value');
                }
                if (! Schema::hasColumn($this->tableName, 'discount_type')) {
                    $table->tinyInteger('discount_type')->nullable()->after('old_discount_type');
                }

                if (! Schema::hasColumn($this->tableName, 'old_discount_1')) {
                    $table->decimal('old_discount_1', 23,6)->nullable()->after('discount_type');
                }
                if (! Schema::hasColumn($this->tableName, 'discount_1')) {
                    $table->decimal('discount_1', 23,6)->default(0.000000)->after('old_discount_1');
                }

                if (! Schema::hasColumn($this->tableName, 'old_discount_2')) {
                     $table->decimal('old_discount_2', 23,6)->nullable()->after('discount_1');
                }
                if (! Schema::hasColumn($this->tableName, 'discount_2')) {
                     $table->decimal('discount_2', 23,6)->default(0.000000)->after('old_discount_2');
                }

                if (! Schema::hasColumn($this->tableName, 'old_discount_3')) {
                     $table->decimal('old_discount_3', 23,6)->nullable()->after('discount_2');
                }
                if (! Schema::hasColumn($this->tableName, 'discount_3')) {
                     $table->decimal('discount_3', 23,6)->default(0.000000)->after('old_discount_3');
                }

                if (! Schema::hasColumn($this->tableName, 'old_discount_4')) {
                    $table->decimal('old_discount_4', 23,6)->nullable()->after('discount_3');
                }
                if (! Schema::hasColumn($this->tableName, 'discount_4')) {
                     $table->decimal('discount_4', 23,6)->default(0.000000)->after('old_discount_4');
                }
                if (! Schema::hasColumn($this->tableName, 'old_adjustment')) {
                     $table->decimal('old_adjustment', 23,6)->nullable()->after('discount_4');
                }
                if (! Schema::hasColumn($this->tableName, 'adjustment')) {
                     $table->decimal('adjustment', 23,6)->default(0.000000)->after('old_adjustment');
                }
                if (! Schema::hasColumn($this->tableName, 'old_net_amount')) {
                     $table->decimal('old_net_amount', 23,6)->nullable()->after('adjustment');
                }
                if (! Schema::hasColumn($this->tableName, 'old_percentage_change')) {
                     $table->decimal('old_percentage_change', 23,6)->nullable()->after('old_net_amount');
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
                if (Schema::hasColumn($this->tableName, 'old_discount_type')) {
                    $table->dropColumn('old_discount_type');
                }
                if (Schema::hasColumn($this->tableName, 'discount_type')) {
                    $table->dropColumn('discount_type');
                }
                if (Schema::hasColumn($this->tableName, 'old_discount_1')) {
                    $table->dropColumn('old_discount_1');
                }
                if (Schema::hasColumn($this->tableName, 'discount_1')) {
                    $table->dropColumn('discount_1');
                }
                if (Schema::hasColumn($this->tableName, 'old_discount_2')) {
                    $table->dropColumn('old_discount_2');
                }
                if (Schema::hasColumn($this->tableName, 'discount_2')) {
                    $table->dropColumn('discount_2');
                }
                if (Schema::hasColumn($this->tableName, 'old_discount_3')) {
                    $table->dropColumn('old_discount_3');
                }
                if (Schema::hasColumn($this->tableName, 'discount_3')) {
                    $table->dropColumn('discount_3');
                }
                if (Schema::hasColumn($this->tableName, 'old_discount_4')) {
                    $table->dropColumn('old_discount_4');
                }
                if (Schema::hasColumn($this->tableName, 'discount_4')) {
                    $table->dropColumn('discount_4');
                }
                if (Schema::hasColumn($this->tableName, 'old_adjustment')) {
                    $table->dropColumn('old_adjustment');
                }
                if (Schema::hasColumn($this->tableName, 'adjustment')) {
                    $table->dropColumn('adjustment');
                }
                if (Schema::hasColumn($this->tableName, 'old_net_amount')) {
                    $table->dropColumn('old_net_amount');
                }
                if (Schema::hasColumn($this->tableName, 'old_percentage_change')) {
                    $table->dropColumn('old_percentage_change');
                }
            });
        }
    }
}
