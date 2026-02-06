<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interval_blocks', function (Blueprint $table) {
            $table->id();
            $table->integer('duration_seconds')->nullable();
            $table->integer('distance_meters')->nullable();
            $table->integer('target_pace_seconds_per_km')->nullable();
            $table->integer('target_heart_rate_zone')->nullable();
            $table->string('intensity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interval_blocks');
    }
};
