<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referee_exam_periods', function (Blueprint $table) {
            $table->id();
            $table->year('season_year')->unique();
            $table->date('opens_at');
            $table->date('closes_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('referee_medical_exams', function (Blueprint $table) {
            $table->year('season_year')->nullable()->after('referee_id');
            $table->foreignId('recorded_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        Schema::table('referee_physical_tests', function (Blueprint $table) {
            $table->year('season_year')->nullable()->after('referee_id');
            $table->foreignId('recorded_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        $this->assignSeasonsToLatestResults('referee_medical_exams', 'exam_date');
        $this->assignSeasonsToLatestResults('referee_physical_tests', 'test_date');

        Schema::table('referee_medical_exams', function (Blueprint $table) {
            $table->unique(
                ['referee_id', 'season_year'],
                'referee_medical_exams_referee_season_unique'
            );
        });

        Schema::table('referee_physical_tests', function (Blueprint $table) {
            $table->unique(
                ['referee_id', 'season_year'],
                'referee_physical_tests_referee_season_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('referee_medical_exams', function (Blueprint $table) {
            $table->dropUnique('referee_medical_exams_referee_season_unique');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('season_year');
        });

        Schema::table('referee_physical_tests', function (Blueprint $table) {
            $table->dropUnique('referee_physical_tests_referee_season_unique');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('season_year');
        });

        Schema::dropIfExists('referee_exam_periods');
    }

    private function assignSeasonsToLatestResults(string $table, string $dateColumn): void
    {
        $seen = [];

        DB::table($table)
            ->whereNotNull($dateColumn)
            ->orderBy('referee_id')
            ->orderByDesc($dateColumn)
            ->orderByDesc('id')
            ->get(['id', 'referee_id', $dateColumn])
            ->each(function (object $result) use ($table, $dateColumn, &$seen): void {
                $date = CarbonImmutable::parse($result->{$dateColumn});
                $seasonYear = $date->month >= 7 ? $date->year : $date->year - 1;
                $key = $result->referee_id.'-'.$seasonYear;

                if (isset($seen[$key])) {
                    return;
                }

                DB::table($table)->where('id', $result->id)->update(['season_year' => $seasonYear]);
                $seen[$key] = true;
            });
    }
};
