<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fitness_profiles', function (Blueprint $table) {
            $table->boolean('prefer_garmin_exercises')->default(false)->after('minutes_per_session');
        });
    }

    public function down(): void
    {
        Schema::table('fitness_profiles', function (Blueprint $table) {
            $table->dropColumn('prefer_garmin_exercises');
        });
    }
};
