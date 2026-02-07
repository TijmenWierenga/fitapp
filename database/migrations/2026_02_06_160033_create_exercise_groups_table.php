<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_type');
            $table->integer('rounds')->default(1);
            $table->integer('rest_between_rounds_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_groups');
    }
};
