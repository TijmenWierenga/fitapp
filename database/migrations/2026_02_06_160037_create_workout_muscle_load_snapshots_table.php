<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_muscle_load_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->string('muscle_group');
            $table->float('total_load');
            $table->json('source_breakdown');
            $table->datetime('completed_at');
            $table->timestamp('created_at')->nullable();

            $table->unique(['workout_id', 'muscle_group']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_muscle_load_snapshots');
    }
};
