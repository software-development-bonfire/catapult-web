<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNecessaryColumnsInDeviceSettingsTable extends Migration
{
    private $table = 'device_settings';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (! Schema::hasColumn($this->table, 'terminal_code')) {
                    $table->string('terminal_code')->after('bid')->nullable();
                }
                if (! Schema::hasColumn($this->table, 'device_code')) {
                    $table->string('device_code')->after('terminal_code')->nullable();
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
        if (Schema::hasTable($this->table)) {
            Schema::table($this->table, function (Blueprint $table) {
                if (Schema::hasColumn($this->table, 'terminal_code')) {
                    $table->dropColumn('terminal_code');
                }
                if (Schema::hasColumn($this->table, 'device_code')) {
                    $table->dropColumn('device_code');
                }
            });
        }
    }
}
