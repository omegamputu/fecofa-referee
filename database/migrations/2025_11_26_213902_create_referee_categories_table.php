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
        Schema::create('referee_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();     // "Internationale", "Nationale", "Stagiaire"
            $table->string('slug')->unique();     // "internationale", "nationale", "stagiaire"
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referee_categories');
    }
};
