<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_muscle_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->string('muscle_group');
            $table->string('role');
            $table->float('load_factor');
            $table->timestamps();

            $table->unique(['exercise_id', 'muscle_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_muscle_loads');
    }
};
