<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('workout_blocks')->cascadeOnDelete();
            $table->string('type');
            $table->integer('position');
            $table->string('label')->nullable();
            $table->integer('repeat_count')->default(1);
            $table->integer('rest_between_repeats_seconds')->nullable();
            $table->string('blockable_type')->nullable();
            $table->unsignedBigInteger('blockable_id')->nullable();
            $table->timestamps();

            $table->index(['workout_id', 'position']);
            $table->index(['parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_blocks');
    }
};
