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
        Schema::create('referee_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referee_id')
                ->constrained('referees')
                ->cascadeOnDelete();

            $table->year('season_year');   // 2025, 2026...
            $table->enum('status', ['active', 'inactive', 'suspended', 'retired'])->default('active');
            $table->enum('list_type', ['fifa', 'national', 'provincial'])->nullable();
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->unique(['referee_id', 'season_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referee_seasons');
    }
};
