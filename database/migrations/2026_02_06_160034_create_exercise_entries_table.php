<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained();
            $table->integer('position');
            $table->integer('sets');
            $table->integer('reps')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->float('weight_kg')->nullable();
            $table->integer('rpe_target')->nullable();
            $table->integer('rest_between_sets_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['exercise_group_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_entries');
    }
};
