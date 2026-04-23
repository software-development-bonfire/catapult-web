<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPosLocationAndTableIdColumns extends Migration
{
    public function up()
    {
        Schema::table('table_location', function (Blueprint $table) {
            $table->unsignedBigInteger('pos_location_id')->nullable()->after('id')->comment('ID from POS database');
        });

        Schema::table('table', function (Blueprint $table) {
            $table->unsignedBigInteger('pos_table_id')->nullable()->after('id')->comment('ID from POS database');
        });
    }

    public function down()
    {
        Schema::table('table_location', function (Blueprint $table) {
            if (Schema::hasColumn('table_location', 'pos_location_id')) {
                $table->dropColumn('pos_location_id');
            }
        });

        Schema::table('table', function (Blueprint $table) {
            if (Schema::hasColumn('table', 'pos_table_id')) {
                $table->dropColumn('pos_table_id');
            }
        });
    }
}
