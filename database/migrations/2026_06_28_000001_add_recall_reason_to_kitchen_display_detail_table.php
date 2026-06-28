<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add recall_reason column to kitchen_display_detail.
 *
 * Stores the reason code when an item is recalled from FOR_RECALL back to FOR_SERVE.
 */
class AddRecallReasonToKitchenDisplayDetailTable extends Migration
{
    public function up()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_display_detail', 'recall_reason')) {
                $table->string('recall_reason')->nullable()->after('served_at');
            }
        });
    }

    public function down()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_display_detail', 'recall_reason')) {
                $table->dropColumn('recall_reason');
            }
        });
    }
}
