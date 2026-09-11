<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_categories', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id(); $table->foreignId('vehicle_category_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique(); $table->string('brand'); $table->string('model'); $table->string('variant')->nullable();
            $table->unsignedSmallInteger('year'); $table->string('license_plate')->unique(); $table->string('color');
            $table->string('transmission'); $table->string('fuel_type'); $table->unsignedTinyInteger('seat_capacity');
            $table->decimal('daily_price', 15, 2); $table->decimal('hourly_price', 15, 2)->nullable(); $table->decimal('driver_daily_price', 15, 2)->default(0);
            $table->decimal('security_deposit', 15, 2)->default(0); $table->unsignedBigInteger('current_odometer')->default(0); $table->decimal('fuel_capacity', 8, 2)->nullable();
            $table->string('status')->default('AVAILABLE'); $table->string('registration_number')->nullable(); $table->date('stnk_expired_at')->nullable(); $table->date('tax_expired_at')->nullable();
            $table->text('description')->nullable(); $table->boolean('featured')->default(false); $table->timestamps();
        });
        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id(); $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete(); $table->string('file_path'); $table->boolean('is_primary')->default(false); $table->unsignedInteger('sort_order')->default(0); $table->timestamps();
        });
        Schema::create('vehicle_features', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->string('icon')->nullable(); $table->timestamps(); });
        Schema::create('vehicle_feature_pivot', function (Blueprint $table) { $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete(); $table->foreignId('vehicle_feature_id')->constrained()->cascadeOnDelete(); $table->primary(['vehicle_id', 'vehicle_feature_id']); });
    }
    public function down(): void { Schema::dropIfExists('vehicle_feature_pivot'); Schema::dropIfExists('vehicle_features'); Schema::dropIfExists('vehicle_images'); Schema::dropIfExists('vehicles'); Schema::dropIfExists('vehicle_categories'); }
};
