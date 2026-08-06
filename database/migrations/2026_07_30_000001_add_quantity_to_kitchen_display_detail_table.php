<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddQuantityToKitchenDisplayDetailTable extends Migration
{
    public function up()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            $table->decimal('quantity', 14, 6)->default(0)->after('transaction_type');
        });

        // Backfill existing records: set quantity = remaining_quantity
        DB::table('kitchen_display_detail')->update(['quantity' => DB::raw('remaining_quantity')]);
    }

    public function down()
    {
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
}
