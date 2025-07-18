<?php

use App\Enums\CDIS\VatPrincipal;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnInVendorTable extends Migration
{
    protected $tableName = 'cdis_vendor';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'vat_principal')) {
                    $table->tinyInteger('vat_principal')->default(VatPrincipal::VATABLE)->after('address');
                }
                if (! Schema::hasColumn($this->tableName, 'country')) {
                    $table->integer('country')->nullable()->after('vat_principal');
                }
                if (! Schema::hasColumn($this->tableName, 'region_bid')) {
                    $table->unsignedBigInteger('region_bid')->nullable()->after('country');
                }
                if (! Schema::hasColumn($this->tableName, 'province_bid')) {
                    $table->unsignedBigInteger('province_bid')->nullable()->after('region_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'city_bid')) {
                    $table->unsignedBigInteger('city_bid')->nullable()->after('province_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'barangay_bid')) {
                    $table->unsignedBigInteger('barangay_bid')->nullable()->after('city_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'zip_code')) {
                    $table->string('zip_code')->nullable()->after('barangay_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'street_building_house_number')) {
                    $table->string('street_building_house_number')->nullable()->default('')->after('zip_code');
                }
                if (! Schema::hasColumn($this->tableName, 'email_2')) {
                    $table->string('email_2')->nullable()->after('email');
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
                if (Schema::hasColumn($this->tableName, 'vat_principal')) {
                    $table->dropColumn('vat_principal');
                }
                if (Schema::hasColumn($this->tableName, 'country')) {
                    $table->dropColumn('country');
                }
                if (Schema::hasColumn($this->tableName, 'region_bid')) {
                    $table->dropColumn('region_bid');
                }
                if (Schema::hasColumn($this->tableName, 'province_bid')) {
                    $table->dropColumn('province_bid');
                }
                if (Schema::hasColumn($this->tableName, 'city_bid')) {
                    $table->dropColumn('city_bid');
                }
                if (Schema::hasColumn($this->tableName, 'barangay_bid')) {
                    $table->dropColumn('barangay_bid');
                }
                if (Schema::hasColumn($this->tableName, 'zip_code')) {
                    $table->dropColumn('zip_code');
                }
                if (Schema::hasColumn($this->tableName, 'street_building_house_number')) {
                    $table->dropColumn('street_building_house_number');
                }
                if (Schema::hasColumn($this->tableName, 'email_2')) {
                    $table->dropColumn('email_2');
                }
            });
        }
    }
}
