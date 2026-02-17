<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderTypeIdToKitchenDisplayDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            // Add order_type_id if it doesn't already exist
            if (!Schema::hasColumn('kitchen_display_detail', 'order_type_id')) {
                $table->string('order_type_id')->nullable()->after('order_type_name');
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
            if (Schema::hasColumn('kitchen_display_detail', 'order_type_id')) {
                $table->dropColumn('order_type_id');
            }
        });
    }
}
