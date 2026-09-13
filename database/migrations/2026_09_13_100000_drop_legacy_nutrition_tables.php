<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('meal_items');
        Schema::dropIfExists('meal_analyses');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('weight_logs');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('nutrition_profiles');
        Schema::dropIfExists('foods');
    }

    public function down(): void
    {
        // Legacy nutrition schema intentionally not restored.
    }
};
