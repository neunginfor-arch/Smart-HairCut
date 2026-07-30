<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('promotions', 'image_path')) {
            Schema::table('promotions', function (Blueprint $table): void {
                $table->string('image_path')->nullable();
            });
        }

        if (! Schema::hasColumn('promotions', 'button_text')) {
            Schema::table('promotions', function (Blueprint $table): void {
                $table->string('button_text', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('promotions', 'button_url')) {
            Schema::table('promotions', function (Blueprint $table): void {
                $table->string('button_url', 2048)->nullable();
            });
        }

        if (! Schema::hasColumn('promotions', 'display_order')) {
            Schema::table('promotions', function (Blueprint $table): void {
                $table->unsignedSmallInteger('display_order')->default(0);
            });
        }

        if (! Schema::hasIndex('promotions', 'promotions_display_order_index')) {
            Schema::table('promotions', function (Blueprint $table): void {
                $table->index(['display_order', 'id'], 'promotions_display_order_index');
            });
        }
    }

    public function down(): void
    {
        // This repair migration intentionally preserves existing promotion data
        // and columns when rolling back.
    }
};
