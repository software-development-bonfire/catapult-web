<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataConditionColumnInFieldMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('field_mapping', function (Blueprint $table) {
            $table->string('data_condition', 254)->after('primary_table')->nullable();
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
            $table->dropColumn('data_condition');
        });
    }
}
