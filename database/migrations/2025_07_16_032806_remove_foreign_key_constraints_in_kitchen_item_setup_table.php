<?php

use App\Traits\MigrationTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveForeignKeyConstraintsInKitchenItemSetupTable extends Migration
{
    protected $tableName = 'cdis_kitchen_item_setup';

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
            if ($this->hasForeignKey($this->tableName, 'cdis_kitchen_item_setup_device_type_bid_foreign')) {
                $table->dropForeign('cdis_kitchen_item_setup_device_type_bid_foreign');
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
            if (! $this->hasForeignKey($this->tableName, 'cdis_kitchen_item_setup_device_type_bid_foreign')) {
                $table->foreign('device_type_bid')
                    ->references('bid')
                    ->on('cdis_kitchen_device_printer')
                    ->onUpdate('restrict')
                    ->onDelete('cascade');
            }
        });
        Schema::enableForeignKeyConstraints();
    }
}
