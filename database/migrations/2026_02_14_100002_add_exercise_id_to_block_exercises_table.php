<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('block_exercises', function (Blueprint $table): void {
            $table->foreignId('exercise_id')->nullable()->after('block_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('block_exercises', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('exercise_id');
        });
    }
};
