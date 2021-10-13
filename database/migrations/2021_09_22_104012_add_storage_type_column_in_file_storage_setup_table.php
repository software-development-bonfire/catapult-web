<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStorageTypeColumnInFileStorageSetupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_storage_setup', function (Blueprint $table) {
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
        Schema::table('file_storage_setup', function (Blueprint $table) {
            $table->dropColumn('storage_type');
        });
    }
}
