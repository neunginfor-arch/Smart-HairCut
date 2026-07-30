<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('amount');
            $table->index(['status', 'expires_at']);
        });

        DB::table('booking_payments')
            ->where('status', 'pending')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()->addMinutes(10)]);
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
