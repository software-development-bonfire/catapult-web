<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add started_at, end_at timestamps and unique index to kitchen_display_detail.
 *
 * started_at: when the item first arrives at a specific kitchen_station_bid.
 * end_at:     when all quantity for that item at that station becomes zero.
 *
 * Unique index on (head_bid, transaction_product_bid, product_uom_packaging_bid,
 *   kitchen_station_bid, terminal_number) enforces no duplicate rows for
 *   non-null kitchen_station_bid. NULL cases are handled at application level.
 */
class AddTimestampsAndUniqueToKitchenDisplayDetail extends Migration
{
    public function up()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_display_detail', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('terminal_number');
            }
            if (!Schema::hasColumn('kitchen_display_detail', 'end_at')) {
                $table->timestamp('end_at')->nullable()->after('started_at');
            }

            // Unique index on business key (non-null kitchen_station_bid cases are enforced at DB level;
            // null cases are handled by application logic in KitchenDisplayMovementService).
            $table->unique(
                ['head_bid', 'transaction_product_bid', 'product_uom_packaging_bid', 'kitchen_station_bid', 'terminal_number'],
                'uq_kdd_business_key'
            );
        });
    }

    public function down()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            $table->dropUnique('uq_kdd_business_key');

            if (Schema::hasColumn('kitchen_display_detail', 'end_at')) {
                $table->dropColumn('end_at');
            }
            if (Schema::hasColumn('kitchen_display_detail', 'started_at')) {
                $table->dropColumn('started_at');
            }
        });
    }
}
