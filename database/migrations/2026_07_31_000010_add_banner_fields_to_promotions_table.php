<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
            $table->string('button_text', 50)->nullable()->after('image_path');
            $table->string('button_url', 2048)->nullable()->after('button_text');
            $table->unsignedSmallInteger('display_order')->default(0)->after('button_url');
            $table->index(['is_active', 'starts_at', 'ends_at'], 'promotions_active_period_index');
            $table->index(['display_order', 'id'], 'promotions_display_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropIndex('promotions_active_period_index');
            $table->dropIndex('promotions_display_order_index');
            $table->dropColumn(['image_path', 'button_text', 'button_url', 'display_order']);
        });
    }
};
