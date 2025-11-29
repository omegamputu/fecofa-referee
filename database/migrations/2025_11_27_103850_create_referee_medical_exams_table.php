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
        Schema::create('referee_medical_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referee_id')
                ->constrained('referees')
                ->cascadeOnDelete();

            $table->date('exam_date')->nullable();
            $table->enum('result', ['passed', 'failed', 'pending'])->default('pending');
            $table->string('file_path')->nullable(); // certificat médical
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referee_medical_exams');
    }
};
