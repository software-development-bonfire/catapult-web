<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnInTerminalTable extends Migration
{
    protected $tableName = 'cdis_terminal';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'default_sales_location')) {
                    $table->unsignedBigInteger('default_sales_location')->after('branch_bid');
                }
                if (! Schema::hasColumn($this->tableName, 'ptu_date_issued')) {
                    $table->date('ptu_date_issued')->nullable()->after('ptu_no');
                }
                if (! Schema::hasColumn($this->tableName, 'ptu_valid_until')) {
                    $table->date('ptu_valid_until')->nullable()->after('ptu_date_issued');
                }
                if (! Schema::hasColumn($this->tableName, 'accreditation_date_issued')) {
                    $table->date('accreditation_date_issued')->nullable()->after('accreditation_no');
                }
                if (! Schema::hasColumn($this->tableName, 'accreditation_valid_until')) {
                    $table->date('accreditation_valid_until')->nullable()->after('accreditation_date_issued');
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
                if (Schema::hasColumn($this->tableName, 'default_sales_location')) {
                    $table->dropColumn('default_sales_location');
                }
                if (Schema::hasColumn($this->tableName, 'ptu_date_issued')) {
                    $table->dropColumn('ptu_date_issued');
                }
                if (Schema::hasColumn($this->tableName, 'ptu_valid_until')) {
                    $table->dropColumn('ptu_valid_until');
                }
                if (Schema::hasColumn($this->tableName, 'accreditation_date_issued')) {
                    $table->dropColumn('accreditation_date_issued');
                }
                if (Schema::hasColumn($this->tableName, 'accreditation_valid_until')) {
                    $table->dropColumn('accreditation_valid_until');
                }
            });
        }
    }
}
