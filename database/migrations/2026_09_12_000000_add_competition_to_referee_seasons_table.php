<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('referee_seasons', 'competition')) {
            Schema::table('referee_seasons', function (Blueprint $table) {
                $table->string('competition', 20)
                    ->default('ligue_1')
                    ->after('season_year');
            });
        }

        if (! Schema::hasColumn('referee_seasons', 'designated_by')) {
            Schema::table('referee_seasons', function (Blueprint $table) {
                $table->foreignId('designated_by')
                    ->nullable()
                    ->after('comment')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('referee_seasons', 'designated_at')) {
            Schema::table('referee_seasons', function (Blueprint $table) {
                $table->timestamp('designated_at')->nullable()->after('designated_by');
            });
        }

        if (! Schema::hasIndex('referee_seasons', 'referee_seasons_referee_id_index')) {
            Schema::table('referee_seasons', function (Blueprint $table) {
                $table->index('referee_id', 'referee_seasons_referee_id_index');
            });
        }

        Schema::table('referee_seasons', function (Blueprint $table) {
            $table->dropUnique(['referee_id', 'season_year']);
            $table->unique(
                ['referee_id', 'season_year', 'competition'],
                'referee_seasons_referee_season_competition_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('referee_seasons', function (Blueprint $table) {
            $table->dropUnique('referee_seasons_referee_season_competition_unique');
        });

        DB::table('referee_seasons')
            ->select(['id', 'referee_id', 'season_year'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (object $season) => $season->referee_id.'-'.$season->season_year)
            ->each(function ($seasons): void {
                $duplicateIds = $seasons->skip(1)->pluck('id');

                if ($duplicateIds->isNotEmpty()) {
                    DB::table('referee_seasons')->whereIn('id', $duplicateIds)->delete();
                }
            });

        Schema::table('referee_seasons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('designated_by');
            $table->dropColumn(['competition', 'designated_at']);
            $table->unique(['referee_id', 'season_year']);
        });

        Schema::table('referee_seasons', function (Blueprint $table) {
            $table->dropIndex('referee_seasons_referee_id_index');
        });
    }
};
