<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('category', 60)->index();
            $table->string('severity', 20)->index();
            $table->string('status', 20)->default('open')->index();
            $table->string('title');
            $table->text('message');
            $table->text('recommendation')->nullable();
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cold_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('detected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('detected_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->decimal('metric_value', 14, 3)->nullable();
            $table->decimal('threshold_value', 14, 3)->nullable();
            $table->string('fingerprint', 120)->nullable()->index();
            $table->json('evidence')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity', 'detected_at']);
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};
