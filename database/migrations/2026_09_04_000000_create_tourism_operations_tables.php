<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->uuid('qr_token')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->enum('role', ['admin', 'receptionist'])->default('receptionist');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('lodgings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('address');
            $table->unsignedInteger('rooms')->default(0);
            $table->unsignedInteger('available_rooms')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('tour_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 20)->default('#e00022');
            $table->timestamps();
        });

        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('location');
            $table->unsignedTinyInteger('duration_days')->default(1);
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('size')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('available_stock')->default(0);
            $table->string('image_path')->nullable();
            $table->string('condition')->default('Excelente');
            $table->timestamps();
        });

        Schema::create('tourists', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('nationality');
            $table->string('passport_number')->unique();
            $table->text('food_notes')->nullable();
            $table->string('apparel_size', 20)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->string('hotel')->nullable();
            $table->string('whatsapp', 40);
            $table->string('preferred_language', 10)->default('es');
            $table->timestamps();
        });

        Schema::create('departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->date('tour_date');
            $table->text('includes')->nullable();
            $table->text('excludes')->nullable();
            $table->text('observations')->nullable();
            $table->string('guide')->nullable();
            $table->string('driver')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['tour_package_id', 'tour_date']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tourist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tour_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['qr', 'online'])->default('qr');
            $table->enum('status', ['pending_package', 'pending_equipment', 'confirmed'])->default('pending_package');
            $table->date('tour_date')->nullable();
            $table->text('reception_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('equipment_reservation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('assigned_size', 30)->nullable();
            $table->timestamps();
            $table->unique(['reservation_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_reservation');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('departures');
        Schema::dropIfExists('tourists');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('tour_packages');
        Schema::dropIfExists('tour_categories');
        Schema::dropIfExists('lodgings');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('branches');
    }
};
