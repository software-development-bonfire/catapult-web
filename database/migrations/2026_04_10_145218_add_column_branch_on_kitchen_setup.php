<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnBranchOnKitchenSetup extends Migration
{
     private $tables = [
        'cdis_kitchen_station',
        'cdis_kitchen_station_process',
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $alterTable) {
            if (Schema::hasTable($alterTable)) {
                Schema::table($alterTable, function (Blueprint $table) use ($alterTable) {
                    if (! Schema::hasColumn($alterTable, 'branch_bid')) {
                        $table->unsignedBigInteger('branch_bid')->nullable()->after('code');
                    }
                });
            }
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        foreach ($this->tables as $alterTable) {
            if (Schema::hasTable($alterTable)) {
                Schema::table($alterTable, function (Blueprint $table)  use ($alterTable) {
                    if (Schema::hasColumn($alterTable, 'branch_bid')) {
                        $table->dropColumn('branch_bid');
                    }
                });
            }
        }
        
    }
}
