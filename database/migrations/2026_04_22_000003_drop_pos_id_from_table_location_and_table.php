<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPosIdFromTableLocationAndTable extends Migration
{
    public function up()
    {
        Schema::table('table_location', function (Blueprint $table) {
            if (Schema::hasColumn('table_location', 'pos_id')) {
                $table->dropColumn('pos_id');
            }
        });

        Schema::table('table', function (Blueprint $table) {
            if (Schema::hasColumn('table', 'pos_id')) {
                $table->dropColumn('pos_id');
            }
        });
    }

    public function down()
    {
        Schema::table('table_location', function (Blueprint $table) {
            if (Schema::hasColumn('table_location', 'pos_id')) {
                return;
            }
            $table->unsignedBigInteger('pos_id')->nullable()->after('id');
        });

        Schema::table('table', function (Blueprint $table) {
            if (Schema::hasColumn('table', 'pos_id')) {
                return;
            }
            $table->unsignedBigInteger('pos_id')->nullable()->after('id');
        });
    }
}
