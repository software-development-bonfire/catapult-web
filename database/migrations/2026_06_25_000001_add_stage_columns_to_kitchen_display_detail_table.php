<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add intra-station stage movement columns to kitchen_display_detail.
 *
 * These columns support the Manam fine-dining workflow where items move through
 * stages within a single station (Prepare → Bump → Recall) rather than only
 * between stations.
 *
 * action_type: 1=FOR_PREPARE, 2=FOR_SERVE, 3=FOR_BUMP, 4=FOR_RECALL, 5=FOR_DONE
 * prepared_quantity: quantity that has been prepared
 * bumped_quantity: quantity that has been bumped (ready for serve/recall)
 * released_quantity: quantity that has been released to customer
 * prepared_at: timestamp when item was first prepared
 * bumped_at: timestamp when item was first bumped
 * served_at: timestamp when item was served
 */
class AddStageColumnsToKitchenDisplayDetailTable extends Migration
{
    public function up()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_display_detail', 'action_type')) {
                $table->tinyInteger('action_type')->default(1)->after('status');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'prepared_quantity')) {
                $table->decimal('prepared_quantity', 10, 6)->default(0)->after('remaining_quantity');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'bumped_quantity')) {
                $table->decimal('bumped_quantity', 10, 6)->default(0)->after('prepared_quantity');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'released_quantity')) {
                $table->decimal('released_quantity', 10, 6)->default(0)->after('bumped_quantity');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'prepared_at')) {
                $table->timestamp('prepared_at')->nullable()->after('sent_at');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'bumped_at')) {
                $table->timestamp('bumped_at')->nullable()->after('prepared_at');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'served_at')) {
                $table->timestamp('served_at')->nullable()->after('bumped_at');
            }
        });
    }

    public function down()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            $columns = ['action_type', 'prepared_quantity', 'bumped_quantity', 'released_quantity', 'prepared_at', 'bumped_at', 'served_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('kitchen_display_detail', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
