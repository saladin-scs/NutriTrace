<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('certifiable');
            $table->string('type');
            $table->string('organism')->nullable();
            $table->string('number')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('verification_level', 20)->default('declared');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('certification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certification_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('environmental_metrics', function (Blueprint $table) {
            $table->id();
            $table->morphs('metricable');
            $table->string('provenance', 20)->default('estimated')->index();
            $table->decimal('co2_kg', 14, 4)->nullable();
            $table->decimal('water_liters', 14, 4)->nullable();
            $table->decimal('distance_km', 12, 2)->nullable();
            $table->decimal('packaging_score', 5, 2)->nullable();
            $table->decimal('environmental_score', 5, 2)->nullable();
            $table->json('breakdown')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('measured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_metrics');
        Schema::dropIfExists('certification_documents');
        Schema::dropIfExists('certifications');
    }
};
