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
        Schema::create('workout_injury_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('injury_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('discomfort_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workout_id', 'injury_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_injury_evaluations');
    }
};
