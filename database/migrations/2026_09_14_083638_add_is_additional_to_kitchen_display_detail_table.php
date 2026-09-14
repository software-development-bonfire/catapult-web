<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAdditionalToKitchenDisplayDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (! Schema::hasColumn('kitchen_display_detail', 'is_additional')) {
                $table->tinyInteger('is_additional')->default(0)->after('is_addon');
            }
            if (! Schema::hasColumn('kitchen_display_detail', 'is_removed')) {
                $table->tinyInteger('is_removed')->default(0)->after('is_additional');
            }
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_display_detail', 'is_additional')) {
                $table->dropColumn('is_additional');
            }
            if (Schema::hasColumn('kitchen_display_detail', 'is_removed')) {
                $table->dropColumn('is_removed');
            }
        });
    }
}
