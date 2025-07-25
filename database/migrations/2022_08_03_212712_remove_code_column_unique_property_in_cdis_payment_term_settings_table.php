<?php

use App\Traits\MigrationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        // I don't know why it allows in CDIS, or due to some testing
        // it creates a duplicates code. Hoping that this will not occurs in production
        // NOTE: This is not a fix, this is just a reporting about the code duplicates.
        $duplicatesExist = DB::table('cdis_payment_term_settings')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicatesExist) {
            throw new \Exception('Cannot add unique constraint to "code" column because duplicates exist.');
        }

        Schema::table('cdis_payment_term_settings', function (Blueprint $table) {
            if (Schema::hasColumn('cdis_payment_term_settings', 'code')) {
                $table->unique(['code']);
            }
        });
    }
}
