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
        Schema::create('exercise_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('set_number');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->unsignedInteger('set_duration')->nullable();
            $table->decimal('distance', 10, 2)->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedSmallInteger('avg_heart_rate')->nullable();
            $table->unsignedSmallInteger('max_heart_rate')->nullable();
            $table->unsignedSmallInteger('avg_pace')->nullable();
            $table->unsignedSmallInteger('avg_power')->nullable();
            $table->unsignedSmallInteger('max_power')->nullable();
            $table->unsignedSmallInteger('avg_cadence')->nullable();
            $table->unsignedSmallInteger('total_ascent')->nullable();
            $table->timestamps();

            $table->index(['block_exercise_id', 'set_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_sets');
    }
};
