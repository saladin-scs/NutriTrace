<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_channels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('type', 40)->index();
            $table->string('status', 20)->default('active')->index();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('distribution_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cold_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('node_type', 40)->index();
            $table->string('code', 40);
            $table->string('name');
            $table->string('status', 20)->default('active')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['distribution_channel_id', 'code']);
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('registration', 40)->unique();
            $table->string('type', 40)->default('truck');
            $table->string('fuel_type', 40)->default('diesel');
            $table->string('status', 20)->default('available')->index();
            $table->decimal('capacity_kg', 14, 3)->nullable();
            $table->decimal('emission_factor', 10, 4)->nullable()->comment('kg CO2e per km');
            $table->string('driver_name')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('distribution_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_node_id')->constrained('distribution_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('distribution_nodes')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->unsignedInteger('estimated_duration_min')->nullable();
            $table->string('transport_mode', 40)->nullable();
            $table->decimal('capacity_kg', 14, 3)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->decimal('environmental_impact_kg_co2e', 12, 3)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['distribution_channel_id', 'from_node_id', 'to_node_id'], 'dist_links_channel_from_to_unique');
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name')->nullable();
            $table->foreignId('origin_node_id')->nullable()->constrained('distribution_nodes')->nullOnDelete();
            $table->foreignId('destination_node_id')->nullable()->constrained('distribution_nodes')->nullOnDelete();
            $table->foreignId('origin_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->unsignedInteger('estimated_duration_min')->nullable();
            $table->unsignedInteger('actual_duration_min')->nullable();
            $table->unsignedSmallInteger('stops_count')->default(0);
            $table->string('status', 20)->default('planned')->index();
            $table->json('waypoints')->nullable();
            $table->decimal('estimated_co2e_kg', 12, 3)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('distribution_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('distribution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('to_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('origin_node_id')->nullable()->constrained('distribution_nodes')->nullOnDelete();
            $table->foreignId('destination_node_id')->nullable()->constrained('distribution_nodes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_quantity', 14, 3)->nullable();
            $table->string('unit', 30)->default('kg');
            $table->decimal('load_kg', 14, 3)->nullable();
            $table->timestamp('eta_at')->nullable()->index();
            $table->timestamp('dispatched_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->decimal('estimated_co2e_kg', 12, 3)->nullable();
            $table->decimal('current_temperature_c', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('unit', 30)->default('kg');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'batch_id']);
        });

        Schema::create('vehicle_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->timestamp('recorded_at')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_positions');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('distribution_links');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('distribution_nodes');
        Schema::dropIfExists('distribution_channels');
    }
};
