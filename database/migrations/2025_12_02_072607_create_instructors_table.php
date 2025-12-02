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
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referee_category_id')
                ->constrained('referee_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('referee_role_id')
                ->constrained('referee_roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('instructor_role_id')
                ->constrained('instructor_roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            
            // Infos perso
            $table->string('last_name');          // NOM
            $table->string('first_name');         // Prénoms
            $table->year('year_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            // Infos d’étude et profession
            $table->string('education_level')->nullable(); // G3, L2, etc.
            $table->string('profession')->nullable();

            // Arbitrage
            $table->year('start_year')->nullable();        // Année début arb.
            $table->boolean('is_active')->default(true);

            // Photo de profil (chemin storage)
            $table->string('profile_photo_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
