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
            $table->decimal('subtotal', 10, 2)->nullable()->after('booking_id');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal');
            $table->foreignId('coupon_usage_id')
                ->nullable()
                ->after('discount_amount')
                ->unique()
                ->constrained('coupon_usages')
                ->nullOnDelete();
        });

        DB::table('booking_payments')
            ->whereNull('subtotal')
            ->update(['subtotal' => DB::raw('amount')]);

        Schema::table('point_histories', function (Blueprint $table) {
            $table->foreignId('booking_payment_id')
                ->nullable()
                ->after('booking_id')
                ->unique()
                ->constrained('booking_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('point_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_payment_id');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_usage_id');
            $table->dropColumn(['subtotal', 'discount_amount']);
        });
    }
};
