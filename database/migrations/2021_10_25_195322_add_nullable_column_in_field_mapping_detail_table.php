<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullableColumnInFieldMappingDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('field_mapping_detail', function (Blueprint $table) {
            $table->tinyInteger('nullable')->after('is_primary_key');
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
            $table->dropColumn('nullable');
        });
    }
}
