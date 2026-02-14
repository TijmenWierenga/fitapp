<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->string('force')->nullable();
            $table->string('level');
            $table->string('mechanic')->nullable();
            $table->string('equipment')->nullable();
            $table->string('category');
            $table->json('instructions')->default('[]');
            $table->json('aliases')->nullable();
            $table->text('description')->nullable();
            $table->json('tips')->nullable();
            $table->timestamps();
        });

        Schema::create('exercise_muscle_group', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muscle_group_id')->constrained()->cascadeOnDelete();
            $table->decimal('load_factor', 3, 2);
            $table->timestamps();

            $table->unique(['exercise_id', 'muscle_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_muscle_group');
        Schema::dropIfExists('exercises');
    }
};
