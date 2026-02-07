<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rest_blocks', function (Blueprint $table) {
            $table->id();
            $table->integer('duration_seconds');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rest_blocks');
    }
};
