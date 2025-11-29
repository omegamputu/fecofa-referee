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
        Schema::create('referee_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();     // "Arbitre", "Arbitre assistant", "VMO"
            $table->string('slug')->unique();     // "referee", "assistant_referee", "vmo"
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referee_roles');
    }
};
