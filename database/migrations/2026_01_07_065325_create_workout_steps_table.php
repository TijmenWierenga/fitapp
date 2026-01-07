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
            $table->foreignId('parent_id')->nullable()->constrained('workout_steps')->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->string('type'); // step, repetition
            $table->string('intensity')->nullable();
            $table->string('duration_type');
            $table->string('duration_value')->nullable();
            $table->string('target_type')->nullable();
            $table->string('target_value_low')->nullable();
            $table->string('target_value_high')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
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
