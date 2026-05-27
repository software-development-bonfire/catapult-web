<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add max_preparation_time to kitchen_display_detail and ended_at to kitchen_display.
 *
 * max_preparation_time: The expected preparation time in minutes for this detail item.
 *   Used to determine if an item is delayed based on started_at or transaction_date.
 *
 * ended_at: When the transaction was marked as ended/completed on the station.
 *   Used for in-progress transactions to calculate actual time taken.
 */
class AddMaxPreparationTimeAndEndedAtColumns extends Migration
{
    public function up()
    {
        // Add max_preparation_time to kitchen_display_detail
        if (!Schema::hasColumn('kitchen_display_detail', 'max_preparation_time')) {
            Schema::table('kitchen_display_detail', function (Blueprint $table) {
                $table->unsignedInteger('max_preparation_time')->nullable()->comment('Preparation time in minutes')->after('end_at');
            });
        }

        // Add ended_at to kitchen_display
        if (!Schema::hasColumn('kitchen_display', 'ended_at')) {
            Schema::table('kitchen_display', function (Blueprint $table) {
                $table->dateTime('ended_at')->nullable()->comment('When transaction was ended/stopped on the station')->after('completed_at');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('kitchen_display_detail', 'max_preparation_time')) {
            Schema::table('kitchen_display_detail', function (Blueprint $table) {
                $table->dropColumn('max_preparation_time');
            });
        }

        if (Schema::hasColumn('kitchen_display', 'ended_at')) {
            Schema::table('kitchen_display', function (Blueprint $table) {
                $table->dropColumn('ended_at');
            });
        }
    }
}
