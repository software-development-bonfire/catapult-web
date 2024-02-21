<?php

use App\Enums\Status;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateCdisCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('cdis_customer')) {
            Schema::create('cdis_customer', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bid')->index()->unique();
                $table->string('code', 45);
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('suffix_name')->nullable();
                $table->tinyInteger('created_from');
                $table->string('address')->nullable();
                $table->string('contact_number');
                $table->string('email_address');
                $table->tinyInteger('gender');
                $table->date('birthday');
                $table->tinyInteger('type');
                $table->string('business_style')->nullable();
                $table->string('tin');
                $table->unsignedBigInteger('payment_term_settings_bid')->nullable();
                $table->tinyInteger('status')->default(Status::ACTIVE);
                $table->string('remarks');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
                $table->softDeletes();
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
        Schema::dropIfExists('cdis_customer');
    }
}
