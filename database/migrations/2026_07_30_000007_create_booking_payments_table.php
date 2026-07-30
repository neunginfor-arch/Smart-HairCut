<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('slip_path')->nullable();
            $table->string('transaction_ref')->nullable()->unique();
            $table->timestamp('transferred_at')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('from_bank')->nullable();
            $table->string('to_bank')->nullable();
            $table->string('to_account_no')->nullable();
            $table->json('verified_payload')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
