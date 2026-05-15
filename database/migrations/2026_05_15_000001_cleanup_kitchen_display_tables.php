<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup and reconstruct kitchen_display and kitchen_display_detail tables.
 * 
 * Drops existing tables and recreates them with the proper base structure
 * plus additional columns needed for the unified KDS movement system.
 */
class CleanupKitchenDisplayTables extends Migration
{
    public function up()
    {
        // Drop movement history first (depends on kitchen_display_detail)
        Schema::dropIfExists('kitchen_display_movement_history');

        // Drop detail table (depends on kitchen_display)
        Schema::dropIfExists('kitchen_display_detail');

        // Drop head table
        Schema::dropIfExists('kitchen_display');

        // ============================================
        // Recreate kitchen_display (head record)
        // ============================================
        Schema::create('kitchen_display', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('transaction_detail_bid');
            $table->date('transaction_date')->nullable();
            $table->string('transaction_id', 50)->nullable();
            $table->unsignedBigInteger('terminal_bid')->nullable();
            $table->string('terminal_number', 20)->nullable();
            $table->decimal('total_quantity', 23, 6)->default(0);
            $table->decimal('completed_quantity', 23, 6)->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('transaction_detail_bid')
                ->references('bid')
                ->on('cdis_terminal_transaction_detail')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->index('transaction_id');
            $table->index('terminal_bid');
        });

        // ============================================
        // Recreate kitchen_display_detail
        // ============================================
        Schema::create('kitchen_display_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bid')->index()->unique();
            $table->unsignedBigInteger('head_bid');
            $table->unsignedBigInteger('transaction_product_bid');
            $table->unsignedBigInteger('product_uom_packaging_bid')->nullable();
            $table->string('transaction_id', 50)->nullable();
            $table->decimal('remaining_quantity', 23, 6)->default(0.000000);
            $table->unsignedBigInteger('kitchen_station_bid')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('order_type_id')->nullable();
            $table->string('order_type_name', 50)->nullable();
            $table->string('usage_type', 20)->nullable();
            $table->text('special_request')->nullable();
            $table->text('addons')->nullable();
            $table->boolean('is_addon')->default(false);
            $table->string('name', 255)->nullable();
            $table->string('terminal_number', 20)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign('head_bid')
                ->references('bid')
                ->on('kitchen_display')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('transaction_product_bid')
                ->references('bid')
                ->on('cdis_terminal_transaction_product')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('kitchen_station_bid')
                ->references('bid')
                ->on('cdis_kitchen_station')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->index('transaction_id');
            $table->index(['head_bid', 'kitchen_station_bid']);
        });

        // ============================================
        // Recreate kitchen_display_movement_history
        // ============================================
        Schema::create('kitchen_display_movement_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('detail_bid');
            $table->tinyInteger('from_station_index')->default(0);
            $table->tinyInteger('to_station_index')->default(0);
            $table->decimal('quantity_moved', 23, 6)->default(0);
            $table->enum('movement_type', ['FORWARD', 'BACKWARD', 'TO_RELEASING'])->default('FORWARD');
            $table->string('status_before', 20)->nullable();
            $table->string('status_after', 20)->nullable();
            $table->timestamps();

            $table->index(['detail_bid', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kitchen_display_movement_history');
        Schema::dropIfExists('kitchen_display_detail');
        Schema::dropIfExists('kitchen_display');
    }
}
