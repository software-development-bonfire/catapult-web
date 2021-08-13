<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisBranchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_branch', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('code')->index();
            $table->string('name', 64);
            $table->string('address', 512)->default('');
            $table->string('contact_person', 45)->default('');
            $table->string('contact_number', 128)->default('');
            $table->string('business_name', 256)->default('');
            $table->integer('type')->default(\App\Enums\CDIS\BranchType::COMPANY_OWNED);
            $table->string('tin_no', 128)->nullable();
            $table->integer('status')->default(\App\Enums\CDIS\BranchStatus::ACTIVE);
            $table->tinyInteger('is_main_branch')->default(0);
            $table->time('start_operation_hour')->default('00:00:00');
            $table->time('end_operation_hour')->default('23:59:59');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdis_branch');
    }
}
