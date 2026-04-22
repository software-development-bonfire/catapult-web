<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPosIdFromTableLocationAndTable extends Migration
{
    public function up()
    {
        Schema::table('table_location', function (Blueprint $table) {
            $table->dropColumn('pos_id');
        });

        Schema::table('table', function (Blueprint $table) {
            $table->dropColumn('pos_id');
        });
    }

    public function down()
    {
        Schema::table('table_location', function (Blueprint $table) {
            $table->unsignedBigInteger('pos_id')->nullable()->after('id');
        });

        Schema::table('table', function (Blueprint $table) {
            $table->unsignedBigInteger('pos_id')->nullable()->after('id');
        });
    }
}
