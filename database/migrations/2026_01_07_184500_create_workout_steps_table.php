<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workout_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_step_id')->nullable()->constrained('workout_steps')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            
            // Step type and intensity
            $table->enum('step_kind', ['warmup', 'run', 'recovery', 'cooldown', 'repeat']);
            $table->enum('intensity', ['warmup', 'active', 'rest', 'cooldown']);
            $table->string('name')->nullable();
            $table->text('notes')->nullable();
            
            // Normal step fields (when step_kind != 'repeat')
            $table->enum('duration_type', ['time', 'distance', 'lap_press'])->nullable();
            $table->integer('duration_value')->nullable(); // seconds or meters
            $table->enum('target_type', ['none', 'heart_rate', 'pace'])->nullable();
            $table->enum('target_mode', ['zone', 'range'])->nullable();
            $table->integer('target_zone')->nullable(); // 1-5
            $table->integer('target_low')->nullable(); // bpm or seconds_per_km
            $table->integer('target_high')->nullable(); // bpm or seconds_per_km
            
            // Repeat step fields (when step_kind == 'repeat')
            $table->integer('repeat_count')->nullable(); // >= 2
            $table->boolean('skip_last_recovery')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['workout_id', 'parent_step_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_steps');
    }
};
