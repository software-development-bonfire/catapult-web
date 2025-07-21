<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnBranchAsVendorBidToVendorTable extends Migration
{
    protected $tableName = 'cdis_vendor';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            if (! Schema::hasColumn($this->tableName, 'branch_as_vendor_bid')) {
                $table->unsignedBigInteger('branch_as_vendor_bid')->nullable()->after('contact_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'branch_as_vendor_bid')) {
                $table->dropColumn('branch_as_vendor_bid');
            }
        });
    }
}
