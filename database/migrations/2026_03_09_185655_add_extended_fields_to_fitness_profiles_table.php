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
        Schema::table('fitness_profiles', function (Blueprint $table) {
            $table->string('experience_level')->nullable()->after('prefer_garmin_exercises');
            $table->date('date_of_birth')->nullable()->after('experience_level');
            $table->string('biological_sex')->nullable()->after('date_of_birth');
            $table->decimal('body_weight_kg', 5, 2)->nullable()->after('biological_sex');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('body_weight_kg');
            $table->boolean('has_gym_access')->default(false)->after('height_cm');
            $table->json('home_equipment')->nullable()->after('has_gym_access');
            $table->json('preferred_activities')->nullable()->after('home_equipment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fitness_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'experience_level',
                'date_of_birth',
                'biological_sex',
                'body_weight_kg',
                'height_cm',
                'has_gym_access',
                'home_equipment',
                'preferred_activities',
            ]);
        });
    }
};
