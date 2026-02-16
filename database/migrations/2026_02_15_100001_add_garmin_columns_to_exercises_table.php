<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->unsignedSmallInteger('garmin_exercise_category')->nullable()->after('tips');
            $table->unsignedSmallInteger('garmin_exercise_name')->nullable()->after('garmin_exercise_category');
            $table->index(['garmin_exercise_category', 'garmin_exercise_name'], 'exercises_garmin_mapping_index');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropIndex('exercises_garmin_mapping_index');
            $table->dropColumn(['garmin_exercise_category', 'garmin_exercise_name']);
        });
    }
};
