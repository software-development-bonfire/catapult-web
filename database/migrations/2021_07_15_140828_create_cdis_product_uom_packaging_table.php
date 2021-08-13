<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdisProductUomPackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdis_product_uom_packaging', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->string('barcode', 128);
            $table->string('description', 512)->nullable();
            $table->string('long_description', 1024)->nullable();
            $table->unsignedBigInteger('product_bid');
            $table->unsignedBigInteger('uom_bid');
            $table->decimal('pack_content', 23, 6)->default(0.000000);
            $table->tinyInteger('is_raw_material')->default(0);
            $table->tinyInteger('is_addon')->default(0);
            $table->tinyInteger('is_default')->default(0);
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
        Schema::dropIfExists('cdis_product_uom_packaging');
    }
}
