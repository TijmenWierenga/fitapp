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
            $table->string('sport')->default('running')->after('name');
        });

        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_step_id')->nullable()->constrained('steps')->cascadeOnDelete();
            $table->integer('sort_order');
            $table->string('step_kind'); // warmup | run | recovery | cooldown | repeat
            $table->string('intensity')->nullable(); // warmup | active | rest | cooldown
            $table->string('name')->nullable();
            $table->text('notes')->nullable();

            // Normal step fields
            $table->string('duration_type')->nullable(); // time | distance | lap_press
            $table->integer('duration_value')->nullable();
            $table->string('target_type')->nullable(); // none | heart_rate | pace
            $table->string('target_mode')->nullable(); // zone | range | null
            $table->integer('target_zone')->nullable();
            $table->integer('target_low')->nullable();
            $table->integer('target_high')->nullable();

            // Repeat step fields
            $table->integer('repeat_count')->nullable();
            $table->boolean('skip_last_recovery')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('steps');

        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('sport');
        });
    }
};
