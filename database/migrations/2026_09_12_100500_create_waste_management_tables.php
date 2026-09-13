<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_valorisable')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('treatment_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('capacity_kg_per_day', 14, 3)->nullable();
            $table->json('accepted_category_slugs')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('waste_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('declared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('generated')->index();
            $table->decimal('quantity', 14, 3);
            $table->string('unit', 30)->default('kg');
            $table->timestamp('generated_at')->index();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('collection_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('waste_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('waste_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('quantity_declared', 14, 3);
            $table->string('unit', 30)->default('kg');
            $table->timestamp('requested_at')->index();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('waste_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collector_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('treatment_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vehicle')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('collected_at')->nullable();
            $table->decimal('quantity_collected', 14, 3)->nullable();
            $table->string('unit', 30)->default('kg');
            $table->string('status', 20)->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('valorization_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_collection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('waste_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('process_type', 40)->index();
            $table->decimal('quantity_in', 14, 3);
            $table->decimal('quantity_valorized', 14, 3)->nullable();
            $table->string('unit', 30)->default('kg');
            $table->timestamp('processed_at')->index();
            $table->string('output_description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valorization_processes');
        Schema::dropIfExists('waste_collections');
        Schema::dropIfExists('collection_requests');
        Schema::dropIfExists('wastes');
        Schema::dropIfExists('treatment_centers');
        Schema::dropIfExists('waste_categories');
    }
};
