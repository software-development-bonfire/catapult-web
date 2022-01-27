<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsPrimaryKeyInFieldMappingDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('field_mapping_detail', function (Blueprint $table) {
            $table->tinyInteger('is_primary_key')->after('required');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('field_mapping_detail', function (Blueprint $table) {
            $table->dropColumn('is_primary_key');
        });
    }
}
