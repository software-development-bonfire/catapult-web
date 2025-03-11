<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnInCDISKitchenUserTable extends Migration
{
    protected $tableName = 'cdis_kitchen_user';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                if (! Schema::hasColumn($this->tableName, 'passcode')) {
                    $table->string('passcode')->after('bid');
                }
                if (Schema::hasColumn($this->tableName, 'username')) {
                    $table->string('username')->nullable()->change();
                }
                if (Schema::hasColumn($this->tableName, 'password')) {
                    $table->string('password')->nullable()->change();
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
                if (Schema::hasColumn($this->tableName, 'passcode')) {
                    $table->dropColumn('passcode');
                }
                if (Schema::hasColumn($this->tableName, 'username')) {
                    $table->string('username', 45)->nullable(false)->change();
                }
                if (Schema::hasColumn($this->tableName, 'password')) {
                    $table->string('password', 128)->nullable(false)->change();
                }
            });
        }
    }
}
