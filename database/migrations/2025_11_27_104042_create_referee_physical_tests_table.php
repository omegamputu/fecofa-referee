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
        Schema::create('referee_physical_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referee_id')
                ->constrained('referees')
                ->cascadeOnDelete();

            $table->date('test_date')->nullable();
            $table->enum('result', ['passed', 'failed', 'pending'])->default('pending');
            $table->string('level')->nullable();      // ex. "FIFA high-intensity test"
            $table->string('file_path')->nullable();  // rapport de test
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referee_physical_tests');
    }
};
