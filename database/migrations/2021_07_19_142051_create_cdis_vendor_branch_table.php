<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisVendorBranchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_vendor_branch', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('vendor_bid');
            $table->unsignedBigInteger('branch_bid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_vendor_branch');
    }
}
