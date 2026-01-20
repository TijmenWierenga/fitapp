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
        Schema::table('workouts', function (Blueprint $table) {
            $table->enum('sport', ['running', 'strength', 'cardio', 'hiit'])
                ->default('running')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->string('sport')
                ->default('running')
                ->change();
        });
    }
};
