<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorKitchenDisplayTables extends Migration
{
    /**
     * Refactor kitchen_display and kitchen_display_detail tables
     * Adds support for Fine-Dining (partial orders) and better movement tracking
     *
     * @return void
     */
    public function up()
    {
        
        // Refactor kitchen_display table
        
        Schema::table('kitchen_display', function (Blueprint $table) {
            // Add system mode support (fast-food vs fine-dine)
            if (!Schema::hasColumn('kitchen_display', 'system_mode')) {
                $table->enum('system_mode', ['FASTFOOD', 'FINEDINE'])->default('FASTFOOD');//->after('terminal_number')->index();
            }

            // Add Fine-Dine support
            if (!Schema::hasColumn('kitchen_display', 'order_id')) {
                $table->string('order_id', 50)->nullable()->after('system_mode')->index();
            }

            if (!Schema::hasColumn('kitchen_display', 'batch_number')) {
                $table->integer('batch_number')->default(1)->after('order_id');
            }

            if (!Schema::hasColumn('kitchen_display', 'is_partial')) {
                $table->boolean('is_partial')->default(false)->after('batch_number');
            }

            if (!Schema::hasColumn('kitchen_display', 'is_complete')) {
                $table->boolean('is_complete')->default(false)->after('is_partial');
            }

            // Add metadata
            if (!Schema::hasColumn('kitchen_display', 'total_items')) {
                $table->integer('total_items')->default(0)->after('is_complete');
            }

            if (!Schema::hasColumn('kitchen_display', 'completed_items')) {
                $table->integer('completed_items')->default(0)->after('total_items');
            }

            // Add status enum
            if (!Schema::hasColumn('kitchen_display', 'status')) {
                $table->enum('status', ['PREPARING', 'READY', 'RELEASING', 'DONE'])->default('PREPARING')->after('completed_items')->index();
            }

            // Add indices for better querying
            $table->index(['system_mode', 'status']);
            //$table->index(['terminal_bid', 'status']);
        });

        
        // Refactor kitchen_display_detail table
        
        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            // Simplify station tracking
            if (!Schema::hasColumn('kitchen_display_detail', 'current_station_index')) {
                $table->integer('current_station_index')->nullable()->after('kitchen_station_bid')->index();
            }

            // Add station sequence
            if (!Schema::hasColumn('kitchen_display_detail', 'station_sequence')) {
                $table->json('station_sequence')->nullable()->after('current_station_index');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'current_position_in_sequence')) {
                $table->integer('current_position_in_sequence')->default(0)->after('station_sequence');
            }

            // Add batch tracking for Fine-Dine
            if (!Schema::hasColumn('kitchen_display_detail', 'order_sequence')) {
                $table->integer('order_sequence')->nullable()->after('current_position_in_sequence');
            }

            if (!Schema::hasColumn('kitchen_display_detail', 'batch_number')) {
                $table->integer('batch_number')->default(1)->after('order_sequence');
            }

            // Add original quantity tracking
            if (!Schema::hasColumn('kitchen_display_detail', 'original_quantity')) {
                $table->integer('original_quantity')->default(0)->after('batch_number');
            }

            // Rename and update status
            if (Schema::hasColumn('kitchen_display_detail', 'status')) {
                Schema::table('kitchen_display_detail', function (Blueprint $table) {
                    $table->enum('status', ['ON_PROCESS', 'RELEASING', 'DONE', 'DELETED'])->change();
                });
            }

            // Add indices for better querying
            $table->index(['head_bid', 'current_station_index']);
            $table->index(['order_sequence', 'batch_number']);
            //$table->index(['status', 'terminal_number']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kitchen_display', function (Blueprint $table) {
            $table->dropColumn([
                'system_mode',
                'order_id',
                'batch_number',
                'is_partial',
                'is_complete',
                'total_items',
                'completed_items',
                'status',
            ]);
        });

        Schema::table('kitchen_display_detail', function (Blueprint $table) {
            $table->dropColumn([
                'current_station_index',
                'station_sequence',
                'current_position_in_sequence',
                'order_sequence',
                'batch_number',
                'original_quantity',
            ]);
        });
    }
}
