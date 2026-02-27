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
        Schema::table('injuries', function (Blueprint $table) {
            $table->string('severity')->nullable()->after('body_part');
            $table->string('side')->nullable()->after('severity');
            $table->text('how_it_happened')->nullable()->after('notes');
            $table->text('current_symptoms')->nullable()->after('how_it_happened');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('injuries', function (Blueprint $table) {
            $table->dropColumn(['severity', 'side', 'how_it_happened', 'current_symptoms']);
        });
    }
};
