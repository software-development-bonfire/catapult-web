<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCounterColumnInSyncFileReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sync_file_references', function (Blueprint $table) {
            $table->integer('counter')->after('storage_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sync_file_references', function (Blueprint $table) {
            $table->dropColumn('counter');
        });
    }
}
