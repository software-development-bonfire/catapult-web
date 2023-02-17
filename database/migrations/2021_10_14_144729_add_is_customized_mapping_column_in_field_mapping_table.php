<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsCustomizedMappingColumnInFieldMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('field_mapping', function (Blueprint $table) {
            $table->tinyInteger('is_customized_mapping')->after('status');
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
            $table->dropColumn('is_customized_mapping');
        });
    }
}
