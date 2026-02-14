<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('slug')->after('name')->nullable();
        });

        DB::table('exercises')->orderBy('id')->each(function (object $exercise): void {
            DB::table('exercises')
                ->where('id', $exercise->id)
                ->update(['slug' => Str::slug($exercise->name)]);
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
