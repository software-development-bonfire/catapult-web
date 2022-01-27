<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferenceBidAndReferenceTableInCdisSyncTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_sync', function (Blueprint $table) {
            $table->string('reference_bid')->after('table_name')->nullable();
            $table->string('reference_table')->after('reference_bid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdis_sync', function (Blueprint $table) {
            $table->dropColumn([
                'reference_bid',
                'reference_table'
            ]);
        });
    }
}
