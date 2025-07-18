<?php

use App\Enums\CDIS\MarketingDiscount;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMarketingDiscountColumnInDiscountSettingsTable extends Migration
{
    protected $tableName = 'cdis_discount_settings';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'marketing_discount')) {
                    $table->tinyInteger('marketing_discount')->default(MarketingDiscount::NO)->after('receipt_count');
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
                if (Schema::hasColumn($this->tableName, 'marketing_discount')) {
                    $table->dropColumn('marketing_discount');
                }
            });
        }
    }
}
