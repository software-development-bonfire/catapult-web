<?php

use App\Traits\MigrationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveCodeColumnUniquePropertyInCdisPaymentTermSettingsTable extends Migration
{
    use MigrationTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdis_payment_term_settings', function (Blueprint $table) {
            if (Schema::hasColumn('cdis_payment_term_settings', 'code')) {
                $table->dropUnique(['code']);
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
        Schema::table('cdis_payment_term_settings', function (Blueprint $table) {
            if (Schema::hasColumn('cdis_payment_term_settings', 'code')) {
                $table->unique(['code']);
            }
        });
    }
}
