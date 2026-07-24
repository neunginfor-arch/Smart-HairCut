<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('coupons', function(Blueprint $table){ $table->unsignedInteger('required_points')->nullable()->after('discount_value'); }); } public function down(): void { Schema::table('coupons', function(Blueprint $table){ $table->dropColumn('required_points'); }); } };
