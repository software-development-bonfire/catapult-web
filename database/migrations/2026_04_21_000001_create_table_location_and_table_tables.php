<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableLocationAndTableTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('table_location')) {
            Schema::create('table_location', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 255);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('table')) {
            Schema::create('table', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('location_id')->nullable();
                $table->string('name', 255);
                $table->tinyInteger('status')->default(1);
                $table->enum('availability', ['occupied', 'available', 'reserved'])->default('available');
                $table->timestamps();

                $table->index('location_id');
                $table->index('availability');
                $table->foreign('location_id')->references('id')->on('table_location')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('table')) {
            Schema::table('table', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
            });
            Schema::dropIfExists('table');
        }

        if (Schema::hasTable('table_location')) {
            Schema::dropIfExists('table_location');
        }
    }
}
