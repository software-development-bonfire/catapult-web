<?php

use App\Enums\CDIS\VatPrincipal;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnInBranchTable extends Migration
{
    protected $tableName = 'cdis_branch';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'vat_principal')) {
                    $table->tinyInteger('vat_principal')->default(VatPrincipal::VATABLE)->after('tin_no');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (Schema::hasColumn($this->tableName, 'vat_principal')) {
                    $table->dropColumn('vat_principal');
                }
            });
        }
    }
}
