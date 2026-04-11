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
            $table->unsignedInteger('total_duration')->nullable()->after('feeling');
            $table->decimal('total_distance', 10, 2)->nullable()->after('total_duration');
            $table->unsignedInteger('total_calories')->nullable()->after('total_distance');
            $table->unsignedSmallInteger('avg_heart_rate')->nullable()->after('total_calories');
            $table->unsignedSmallInteger('max_heart_rate')->nullable()->after('avg_heart_rate');
            $table->string('source')->nullable()->after('max_heart_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn([
                'total_duration',
                'total_distance',
                'total_calories',
                'avg_heart_rate',
                'max_heart_rate',
                'source',
            ]);
        });
    }
};
