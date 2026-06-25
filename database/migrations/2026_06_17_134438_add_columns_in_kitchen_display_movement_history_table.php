<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsInKitchenDisplayMovementHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
         Schema::table('kitchen_display_movement_history', function (Blueprint $table) {
            if (! Schema::hasColumn('kitchen_display_movement_history', 'time_duration')) {
                $table->unsignedInteger('time_duration')->nullable()->after('movement_type');
            }

            if (! Schema::hasColumn('kitchen_display_movement_history', 'transaction_detail_bid')) {
                $table->unsignedBigInteger('transaction_detail_bid')->nullable()->after('detail_bid');
            }

            if (! Schema::hasColumn('kitchen_display_movement_history', 'product_uom_bid')) {
                $table->unsignedBigInteger('product_uom_bid')->nullable()->after('transaction_detail_bid');
            }

            if (! Schema::hasColumn('kitchen_display_movement_history', 'remarks')) {
                $table->string('remarks')->nullable()->after('status_after');
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
        Schema::table('kitchen_display_movement_history', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_display_movement_history', 'time_duration')) {
                $table->dropColumn('time_duration');
            }
            if (Schema::hasColumn('kitchen_display_movement_history', 'transaction_detail_bid')) {
                $table->dropColumn('transaction_detail_bid');
            }
            if (Schema::hasColumn('kitchen_display_movement_history', 'product_uom_bid')) {
                $table->dropColumn('product_uom_bid');
            }
            if (Schema::hasColumn('kitchen_display_movement_history', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
}
