<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status', 20)->default('active')->index();
            $table->decimal('capacity_kg', 14, 3)->nullable();
            $table->decimal('target_temp_min_c', 5, 2)->nullable();
            $table->decimal('target_temp_max_c', 5, 2)->nullable();
            $table->decimal('humidity_min_pct', 5, 2)->nullable();
            $table->decimal('humidity_max_pct', 5, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cold_room_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cold_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40)->index();
            $table->timestamp('occurred_at')->index();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('from_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('from_cold_room_id')->nullable()->constrained('cold_rooms')->nullOnDelete();

            $table->foreignId('to_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_cold_room_id')->nullable()->constrained('cold_rooms')->nullOnDelete();

            $table->decimal('quantity', 14, 3);
            $table->string('unit', 30)->default('kg');
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->decimal('humidity_pct', 5, 2)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('event_label')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('traceability_event_id')->nullable()->constrained('traceability_events')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cold_room_id', 'occurred_at']);
            $table->index(['batch_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_room_movements');
        Schema::dropIfExists('cold_rooms');
    }
};
