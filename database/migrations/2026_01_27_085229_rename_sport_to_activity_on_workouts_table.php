<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->string('activity')->default('')->after('name');
        });

        DB::table('workouts')->update([
            'activity' => DB::raw("CASE WHEN sport = 'running' THEN 'run' ELSE sport END"),
        ]);

        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('sport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->string('sport')->after('name');
        });

        DB::table('workouts')->update([
            'sport' => DB::raw("CASE WHEN activity = 'run' THEN 'running' ELSE activity END"),
        ]);

        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('activity');
        });
    }
};
