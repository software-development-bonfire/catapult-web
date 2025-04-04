<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
                if (! Schema::hasColumn($this->table, 'socket_status')) {
                    $table->tinyInteger('socket_status')->after('status')->default(0);
                }
                if (! Schema::hasColumn($this->table, 'last_connected_at')) {
                    $table->timestamp('last_connected_at')->after('socket_status')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->nullable();
                }
                if (! Schema::hasColumn($this->table, 'print_invoice')) {
                    $table->tinyInteger('print_invoice')->after('last_connected_at')->default(0);
                }
                if (! Schema::hasColumn($this->table, 'background_process_priority')) {
                    $table->tinyInteger('background_process_priority')->after('print_invoice')->nullable();
                }
                if (! Schema::hasColumn($this->table, 'kitchen_station_bid')) {
                    $table->bigInteger('kitchen_station_bid')->after('background_process_priority')->nullable();
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
                if (Schema::hasColumn($this->table, 'socket_status')) {
                    $table->dropColumn('socket_status');
                }
                if (Schema::hasColumn($this->table, 'last_connected_at')) {
                    $table->dropColumn('last_connected_at');
                }
                if (Schema::hasColumn($this->table, 'background_process_priority')) {
                    $table->dropColumn('background_process_priority');
                }
                if (Schema::hasColumn($this->table, 'kitchen_station_bid')) {
                    $table->dropColumn('kitchen_station_bid');
                }
            });
        }
    }
}
