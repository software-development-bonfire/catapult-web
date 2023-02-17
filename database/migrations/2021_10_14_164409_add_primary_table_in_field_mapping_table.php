<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrimaryTableInFieldMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('field_mapping', function (Blueprint $table) {
            $table->string('primary_table', 254)->nullable()->after('data_entry');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('field_mapping', function (Blueprint $table) {
            $table->dropColumn('primary_table');
        });
    }
}
