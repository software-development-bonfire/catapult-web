<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisTerminalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_terminal', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('branch_bid');
            $table->string('number', 45)->default(0);
            $table->string('name', 45)->default('');
            $table->integer('status')->default(1);
            $table->string('sql_server', 128)->nullable();
            $table->string('sql_database', 128)->nullable();
            $table->integer('sql_port')->nullable();
            $table->string('sql_username', 128)->nullable();
            $table->string('sql_password', 128)->nullable();
            $table->string('product_license_key', 128)->nullable();
            $table->string('machine_uuid', 128)->nullable();
            $table->string('bir_serial_no', 128)->nullable();
            $table->string('ptu_no', 128)->nullable();
            $table->string('accreditation_no', 128)->nullable();
            $table->string('min', 128)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('branch_bid')
                ->references('bid')
                ->on('cdis_branch')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_terminal');
    }
}
