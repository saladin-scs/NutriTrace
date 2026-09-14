<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cold_rooms', function (Blueprint $table) {
            $table->string('type', 40)->default('refrigerated')->after('code');
            $table->foreignId('owner_organization_id')->nullable()->after('organization_id')->constrained('organizations')->nullOnDelete();
            $table->decimal('occupied_capacity_kg', 14, 3)->default(0)->after('capacity_kg');
            $table->decimal('current_temperature_c', 5, 2)->nullable()->after('target_temp_max_c');
            $table->decimal('energy_kwh_day', 10, 2)->nullable()->after('humidity_max_pct');
            $table->timestamp('last_inspection_at')->nullable()->after('energy_kwh_day');
            $table->string('declared_status', 40)->nullable()->after('status');
            $table->index('type');
        });

        Schema::create('storage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cold_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('remaining_quantity', 14, 3);
            $table->string('unit', 30)->default('kg');
            $table->string('status', 20)->default('stored')->index();
            $table->timestamp('entered_at')->index();
            $table->timestamp('removed_at')->nullable()->index();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cold_room_id', 'status']);
            $table->index(['batch_id', 'status']);
        });

        Schema::create('temperature_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cold_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('temperature_c', 5, 2);
            $table->decimal('min_threshold_c', 5, 2)->nullable();
            $table->decimal('max_threshold_c', 5, 2)->nullable();
            $table->string('status', 20)->default('normal')->index();
            $table->string('source', 40)->default('manual');
            $table->timestamp('recorded_at')->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cold_room_id', 'recorded_at']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('unit', 30)->default('kg');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->foreignId('cold_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('source_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('storage_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_document')->nullable();
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
            $table->index(['cold_room_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('temperature_records');
        Schema::dropIfExists('storage_records');

        Schema::table('cold_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_organization_id');
            $table->dropColumn([
                'type',
                'occupied_capacity_kg',
                'current_temperature_c',
                'energy_kwh_day',
                'last_inspection_at',
                'declared_status',
            ]);
        });
    }
};
