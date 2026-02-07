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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('order');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_id', 'order']);
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('block_type');
            $table->integer('order');
            $table->integer('rounds')->nullable();
            $table->integer('rest_between_exercises')->nullable();
            $table->integer('rest_between_rounds')->nullable();
            $table->integer('time_cap')->nullable();
            $table->integer('work_interval')->nullable();
            $table->integer('rest_interval')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['section_id', 'order']);
        });

        Schema::create('strength_exercises', function (Blueprint $table) {
            $table->id();
            $table->integer('target_sets')->nullable();
            $table->integer('target_reps_min')->nullable();
            $table->integer('target_reps_max')->nullable();
            $table->decimal('target_weight', 8, 2)->nullable();
            $table->decimal('target_rpe', 3, 1)->nullable();
            $table->string('target_tempo')->nullable();
            $table->integer('rest_after')->nullable();
            $table->timestamps();
        });

        Schema::create('cardio_exercises', function (Blueprint $table) {
            $table->id();
            $table->integer('target_duration')->nullable();
            $table->decimal('target_distance', 10, 2)->nullable();
            $table->integer('target_pace_min')->nullable();
            $table->integer('target_pace_max')->nullable();
            $table->smallInteger('target_heart_rate_zone')->nullable();
            $table->integer('target_heart_rate_min')->nullable();
            $table->integer('target_heart_rate_max')->nullable();
            $table->integer('target_power')->nullable();
            $table->timestamps();
        });

        Schema::create('duration_exercises', function (Blueprint $table) {
            $table->id();
            $table->integer('target_duration')->nullable();
            $table->decimal('target_rpe', 3, 1)->nullable();
            $table->timestamps();
        });

        Schema::create('block_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('order');
            $table->string('exerciseable_type');
            $table->unsignedBigInteger('exerciseable_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['block_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('block_exercises');
        Schema::dropIfExists('duration_exercises');
        Schema::dropIfExists('cardio_exercises');
        Schema::dropIfExists('strength_exercises');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('sections');
    }
};
