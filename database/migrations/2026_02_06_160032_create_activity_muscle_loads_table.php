<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_muscle_loads', function (Blueprint $table) {
            $table->id();
            $table->string('activity');
            $table->string('muscle_group');
            $table->string('role');
            $table->float('load_factor');
            $table->timestamps();

            $table->unique(['activity', 'muscle_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_muscle_loads');
    }
};
