<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBranchBidColumnInFolderCountersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('folder_counters', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_bid')->after('bid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('folder_counters', function (Blueprint $table) {
            $table->dropColumn('branch_bid');
        });
    }
}
