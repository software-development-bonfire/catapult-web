<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsInCdisCostAndPriceChangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_cost_and_price_change', function (Blueprint $table) {
            $table->unsignedBigInteger('is_generated')->nullable()->after('updated_at')->default(\App\Enums\DisplayState::NO);
            $table->timestamp('generated_at')->nullable()->after('is_generated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_cost_and_price_change', function (Blueprint $table) {
            $table->dropColumn('is_generated');
            $table->dropColumn('generated_at');
        });
    }
}