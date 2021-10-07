<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStorageTypeColumnInRemoteSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remote_setups', function (Blueprint $table) {
            $table->tinyInteger('storage_type')->after('name')->default(\App\Enums\StorageType::LOCAL_NETWORK);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remote_setups', function (Blueprint $table) {
            $table->dropColumn('storage_type');
        });
    }
}
