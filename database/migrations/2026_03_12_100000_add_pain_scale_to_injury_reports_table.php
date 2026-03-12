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
        Schema::table('injury_reports', function (Blueprint $table) {
            $table->unsignedTinyInteger('pain_scale')->after('type');
            $table->text('content')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('injury_reports', function (Blueprint $table) {
            $table->dropColumn('pain_scale');
            $table->text('content')->nullable(false)->change();
        });
    }
};
