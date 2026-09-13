<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traceability_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_event_id')->nullable()->constrained('traceability_events')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->decimal('quantity', 14, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'occurred_at']);
        });

        Schema::create('transformations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('input_batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('output_batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('process_name');
            $table->decimal('input_quantity', 14, 3)->nullable();
            $table->decimal('output_quantity', 14, 3)->nullable();
            $table->decimal('loss_quantity', 14, 3)->nullable();
            $table->string('unit', 30)->default('kg');
            $table->timestamp('occurred_at')->index();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('to_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('unit', 30)->default('kg');
            $table->string('transport_mode', 40)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->timestamp('shipped_at')->nullable()->index();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
        Schema::dropIfExists('transformations');
        Schema::dropIfExists('traceability_events');
    }
};
