<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('admin_sessions', function(Blueprint $table){ $table->id(); $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete(); $table->string('token',64)->unique(); $table->timestamp('expires_at'); $table->timestamps(); $table->index(['admin_id','expires_at']); }); } public function down(): void { Schema::dropIfExists('admin_sessions'); } };
