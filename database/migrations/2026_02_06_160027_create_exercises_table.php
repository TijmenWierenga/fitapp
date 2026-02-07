<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->string('equipment');
            $table->string('movement_pattern');
            $table->json('primary_muscles');
            $table->json('secondary_muscles');
            $table->timestamps();

            $table->index('category');
            $table->index('equipment');
            $table->index('movement_pattern');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
