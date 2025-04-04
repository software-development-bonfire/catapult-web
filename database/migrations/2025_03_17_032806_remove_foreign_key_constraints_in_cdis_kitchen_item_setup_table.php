<?php

use App\Traits\MigrationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveForeignKeyConstraintsInCDISKitchenItemSetupTable extends Migration
{
    protected $tableName = 'cdis_kitchen_item_setup';
    protected $foreignKeyName = 'cdis_kitchen_item_setup_device_type_bid_foreign';

    use MigrationTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table($this->tableName, function (Blueprint $table) {
            if ($this->hasForeignKey($this->tableName, $this->foreignKeyName)) {
                $table->dropForeign($this->foreignKeyName);
            }
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table($this->tableName, function (Blueprint $table) {
            if (! $this->hasForeignKey($this->tableName, $this->foreignKeyName)) {
                $table->foreign('device_type_bid')
                    ->references('bid')
                    ->on('kitchen_device_printer')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            }
        });
        Schema::enableForeignKeyConstraints();
    }
}
