<?php

use App\Models\Referees\IdentityDocument;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeExamPeriod;
use App\Models\Referees\RefereeMedicalExam;
use App\Models\Referees\RefereePhysicalTest;
use App\Models\Referees\RefereeRole;
use App\Models\Referees\RefereeSeason;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component
{
    #[Url(as: 'season')]
    public int $seasonYear = 0;

    public function mount(): void
    {
        if ($this->seasonYear < 2000 || $this->seasonYear > 2100) {
            $this->seasonYear = $this->defaultSeasonYear();
        }
    }

    public function updatedSeasonYear(): void
    {
        if ($this->seasonYear < 2000 || $this->seasonYear > 2100) {
            $this->seasonYear = $this->defaultSeasonYear();
        }
    }

    public function with(): array
    {
        $seasonYear = $this->seasonYear;
        $activeOfficials = Referee::query()
            ->where('is_active', true)
            ->whereHas('refereeRole', fn (Builder $query) => $query
                ->whereIn('slug', RefereeRole::officiatingSlugs()));

        $totalReferees = Referee::query()->count();
        $activeOfficialsCount = (clone $activeOfficials)->count();
        $medicalPassedCount = (clone $activeOfficials)
            ->whereHas('medicalExams', fn (Builder $query) => $query
                ->where('season_year', $seasonYear)
                ->where('result', RefereeMedicalExam::RESULT_PASSED))
            ->count();
        $physicalPassedCount = (clone $activeOfficials)
            ->whereHas('physicalTests', fn (Builder $query) => $query
                ->where('season_year', $seasonYear)
                ->where('result', RefereePhysicalTest::RESULT_PASSED))
            ->count();
        $eligibleCount = (clone $activeOfficials)->eligibleForSeason($seasonYear)->count();

        $designatedLigueOneCount = $this->designationQuery($seasonYear, RefereeSeason::COMPETITION_LIGUE_1)->count();
        $designatedLigueTwoCount = $this->designationQuery($seasonYear, RefereeSeason::COMPETITION_LIGUE_2)->count();
        $eligibleNotDesignatedCount = Referee::query()
            ->eligibleForSeason($seasonYear)
            ->whereDoesntHave('seasons', fn (Builder $query) => $query
                ->where('season_year', $seasonYear)
                ->where('status', 'active'))
            ->count();

        $expiredDocumentsCount = IdentityDocument::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->count();
        $soonExpiringDocs = IdentityDocument::query()
            ->with('referee')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', today())
            ->whereDate('expiry_date', '<=', today()->addMonths(3))
            ->orderBy('expiry_date')
            ->take(5)
            ->get();
        $lastReferees = Referee::query()
            ->with(['league', 'refereeCategory', 'refereeRole'])
            ->latest()
            ->take(5)
            ->get();

        $byCategory = (clone $activeOfficials)
            ->selectRaw('referee_category_id, COUNT(*) as total')
            ->groupBy('referee_category_id')
            ->with('refereeCategory')
            ->orderByDesc('total')
            ->get();
        $byLeague = (clone $activeOfficials)
            ->selectRaw('league_id, COUNT(*) as total')
            ->whereNotNull('league_id')
            ->groupBy('league_id')
            ->with('league')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        $examPeriod = RefereeExamPeriod::query()->where('season_year', $seasonYear)->first();
        $currentStartYear = now()->month >= 7 ? now()->year : now()->year - 1;
        $seasonOptions = collect(range($currentStartYear + 1, $currentStartYear - 5))
            ->merge(RefereeExamPeriod::query()->pluck('season_year'))
            ->merge(RefereeSeason::query()->pluck('season_year'))
            ->push($seasonYear)
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $year) => [$year => $year.'–'.($year + 1)])
            ->all();

        return [
            'seasonOptions' => $seasonOptions,
            'examPeriod' => $examPeriod,
            'examPeriodState' => $this->examinationPeriodState($examPeriod),
            'totalReferees' => $totalReferees,
            'activeOfficialsCount' => $activeOfficialsCount,
            'medicalPassedCount' => $medicalPassedCount,
            'physicalPassedCount' => $physicalPassedCount,
            'eligibleCount' => $eligibleCount,
            'medicalNotValidatedCount' => max(0, $activeOfficialsCount - $medicalPassedCount),
            'physicalNotValidatedCount' => max(0, $activeOfficialsCount - $physicalPassedCount),
            'designatedLigueOneCount' => $designatedLigueOneCount,
            'designatedLigueTwoCount' => $designatedLigueTwoCount,
            'eligibleNotDesignatedCount' => $eligibleNotDesignatedCount,
            'expiredDocumentsCount' => $expiredDocumentsCount,
            'lastReferees' => $lastReferees,
            'soonExpiringDocs' => $soonExpiringDocs,
            'byCategory' => $byCategory,
            'byLeague' => $byLeague,
            'maxCategoryCount' => max(1, (int) $byCategory->max('total')),
            'maxLeagueCount' => max(1, (int) $byLeague->max('total')),
        ];
    }

    private function defaultSeasonYear(): int
    {
        return RefereeExamPeriod::query()->open()->orderByDesc('season_year')->value('season_year')
            ?? (now()->month >= 7 ? now()->year : now()->year - 1);
    }

    private function designationQuery(int $seasonYear, string $competition): Builder
    {
        return RefereeSeason::query()
            ->where('season_year', $seasonYear)
            ->where('competition', $competition)
            ->where('status', 'active');
    }

    private function examinationPeriodState(?RefereeExamPeriod $period): array
    {
        if (! $period) {
            return [
                'label' => __('Not scheduled'),
                'description' => __('No pre-season examination period has been scheduled for this season.'),
                'classes' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
            ];
        }

        if ($period->isOpen()) {
            return [
                'label' => __('Open'),
                'description' => __('Results can be recorded from :start to :end.', [
                    'start' => $period->opens_at->format('d/m/Y'),
                    'end' => $period->closes_at->format('d/m/Y'),
                ]),
                'classes' => 'border-green-200 bg-green-50 text-green-900 dark:border-green-800 dark:bg-green-950/40 dark:text-green-200',
            ];
        }

        if (today()->lt($period->opens_at)) {
            return [
                'label' => __('Scheduled'),
                'description' => __('The examination period will open on :date.', ['date' => $period->opens_at->format('d/m/Y')]),
                'classes' => 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-200',
            ];
        }

        return [
            'label' => __('Closed'),
            'description' => __('The examination period closed on :date.', ['date' => $period->closes_at->format('d/m/Y')]),
            'classes' => 'border-neutral-200 bg-neutral-100 text-neutral-800 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200',
        ];
    }
};
?>

<section class="container mx-auto h-full w-full max-w-7xl px-4 py-6 sm:px-6">
    <header class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Seasonal overview of referee readiness and designations.') }}</flux:subheading>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <label class="block min-w-48 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                <span class="mb-1 block">{{ __('Season') }}</span>
                <select wire:model.live="seasonYear"
                    class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white">
                    @foreach ($seasonOptions as $year => $label)
                        <option value="{{ $year }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-wrap gap-2">
                @can('manage_seasons')
                    <flux:button variant="primary" color="green" :href="route('referees.designations.index')" wire:navigate>
                        {{ __('Manage designations') }}
                    </flux:button>
                @endcan
                @can('export_referee_data')
                    <flux:button variant="outline" :href="route('referees.eligible.export', ['season' => $seasonYear])">
                        {{ __('Export eligible PDF') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    </header>

    <div class="mb-6 flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between {{ $examPeriodState['classes'] }}">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold">{{ __('Pre-season examinations') }}</span>
                <span class="rounded-full bg-white/70 px-2 py-0.5 text-xs font-semibold dark:bg-black/20">
                    {{ $examPeriodState['label'] }}
                </span>
            </div>
            <p class="mt-1 text-sm opacity-80">{{ $examPeriodState['description'] }}</p>
        </div>
        @can('admin_access')
            <a href="{{ route('admin.exam-periods.index') }}" wire:navigate
                class="text-sm font-semibold underline underline-offset-4">
                {{ $examPeriod ? __('View examination period') : __('Schedule examination period') }}
            </a>
        @endcan
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Total referees') }}</div>
            <div class="mt-2 text-3xl font-bold">{{ $totalReferees }}</div>
            <div class="mt-1 text-xs text-neutral-500">{{ __(':count active officials', ['count' => $activeOfficialsCount]) }}</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Medical aptitude') }}</div>
            <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $medicalPassedCount }}</div>
            <div class="mt-1 text-xs text-neutral-500">{{ __('Validated for the season') }}</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Physical aptitude') }}</div>
            <div class="mt-2 text-3xl font-bold text-emerald-600">{{ $physicalPassedCount }}</div>
            <div class="mt-1 text-xs text-neutral-500">{{ __('Validated for the season') }}</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/30">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Eligible referees') }}</div>
            <div class="mt-2 text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ $eligibleCount }}</div>
            <div class="mt-1 text-xs text-emerald-700/70 dark:text-emerald-300/70">{{ __('Ready to officiate') }}</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
            <div class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Ligue 1</div>
            <div class="mt-2 text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $designatedLigueOneCount }}</div>
            <div class="mt-1 text-xs text-blue-700/70 dark:text-blue-300/70">{{ __('Designated referees') }}</div>
        </div>
        <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 dark:border-cyan-800 dark:bg-cyan-950/30">
            <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Ligue 2</div>
            <div class="mt-2 text-3xl font-bold text-cyan-700 dark:text-cyan-300">{{ $designatedLigueTwoCount }}</div>
            <div class="mt-1 text-xs text-cyan-700/70 dark:text-cyan-300/70">{{ __('Designated referees') }}</div>
        </div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-[#0E1526] lg:col-span-2">
            <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('Items requiring attention') }}</h2>
            <p class="mb-4 text-sm text-neutral-500">{{ __('Operational checks for the selected season.') }}</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                    <div class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $medicalNotValidatedCount }}</div>
                    <div class="mt-1 text-sm font-medium">{{ __('Medical aptitude not validated') }}</div>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                    <div class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $physicalNotValidatedCount }}</div>
                    <div class="mt-1 text-sm font-medium">{{ __('Physical aptitude not validated') }}</div>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $eligibleNotDesignatedCount }}</div>
                    <div class="mt-1 text-sm font-medium">{{ __('Eligible but not designated') }}</div>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
                    <div class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $expiredDocumentsCount }}</div>
                    <div class="mt-1 text-sm font-medium">{{ __('Expired identity documents') }}</div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-[#0E1526]">
            <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('Quick actions') }}</h2>
            <div class="mt-4 grid gap-2">
                @can('view_referee')
                    <a href="{{ route('referees.index') }}" wire:navigate class="rounded-lg border border-neutral-200 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">{{ __('View all referees') }}</a>
                @endcan
                @can('create_referee')
                    <a href="{{ route('referees.create') }}" wire:navigate class="rounded-lg border border-neutral-200 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">{{ __('Add referee') }}</a>
                @endcan
                @if (auth()->user()->can('edit_referee') && auth()->user()->can('view_referee'))
                    <a href="{{ route('referees.index') }}" wire:navigate class="rounded-lg border border-neutral-200 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">{{ __('Record aptitudes') }}</a>
                @endif
                @if (auth()->user()->can('import_referee_data') && auth()->user()->can('view_referee'))
                    <a href="{{ route('referees.index') }}" wire:navigate class="rounded-lg border border-neutral-200 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">{{ __('Import CSV') }}</a>
                @endif
                @can('manage_seasons')
                    <a href="{{ route('referees.designations.index') }}" wire:navigate class="rounded-lg border border-neutral-200 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">{{ __('Manage designations') }}</a>
                @endcan
                @can('create_instructor')
                    <a href="{{ route('instructors.create') }}" wire:navigate class="rounded-lg border border-neutral-200 px-3 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900">{{ __('Add instructor') }}</a>
                @endcan
            </div>
        </section>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-[#0E1526]">
            <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('Breakdown by league') }}</h2>
            <div class="mt-4 space-y-4">
                @forelse ($byLeague as $row)
                    <div>
                        <div class="mb-1 flex justify-between gap-3 text-sm">
                            <span>{{ $row->league?->code ?? __('Unknown') }}</span>
                            <span class="font-semibold">{{ $row->total }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                            <div class="h-full rounded-full bg-blue-600" style="width: {{ max(4, round(($row->total / $maxLeagueCount) * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">{{ __('No data available at this time.') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-[#0E1526]">
            <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('Breakdown by category') }}</h2>
            <div class="mt-4 space-y-4">
                @forelse ($byCategory as $row)
                    <div>
                        <div class="mb-1 flex justify-between gap-3 text-sm">
                            <span>{{ $row->refereeCategory?->name ?? __('Unknown') }}</span>
                            <span class="font-semibold">{{ $row->total }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                            <div class="h-full rounded-full bg-emerald-600" style="width: {{ max(4, round(($row->total / $maxCategoryCount) * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">{{ __('No data available at this time.') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('Latest registered referees') }}</h2>
                @can('view_referee')
                    <a href="{{ route('referees.index') }}" wire:navigate class="text-sm font-medium text-blue-600 hover:underline">{{ __('View all') }}</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-neutral-200 text-xs uppercase text-neutral-500 dark:border-neutral-700">
                        <tr>
                            <th class="py-2 text-left">{{ __('Referee') }}</th>
                            <th class="py-2 text-left">{{ __('League') }}</th>
                            <th class="py-2 text-left">{{ __('Category') }}</th>
                            <th class="py-2 text-right">{{ __('Added on') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse ($lastReferees as $referee)
                            <tr>
                                <td class="py-3 pr-3">
                                    @can('view_referee')
                                        <a href="{{ route('referees.show', $referee) }}" wire:navigate class="font-medium hover:text-blue-600 hover:underline">{{ $referee->fullName() }}</a>
                                    @else
                                        <span class="font-medium">{{ $referee->fullName() }}</span>
                                    @endcan
                                    <div class="text-xs text-neutral-500">{{ $referee->refereeRole?->name }}</div>
                                </td>
                                <td class="py-3 pr-3">{{ $referee->league?->code ?? '—' }}</td>
                                <td class="py-3 pr-3">{{ $referee->refereeCategory?->name ?? '—' }}</td>
                                <td class="py-3 text-right text-neutral-500">{{ $referee->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-neutral-500">{{ __('No referees have been registered yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-[#0E1526]">
            <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('Documents expiring soon') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-neutral-200 text-xs uppercase text-neutral-500 dark:border-neutral-700">
                        <tr>
                            <th class="py-2 text-left">{{ __('Referee') }}</th>
                            <th class="py-2 text-left">{{ __('Type') }}</th>
                            <th class="py-2 text-right">{{ __('Expiry date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        @forelse ($soonExpiringDocs as $document)
                            <tr>
                                <td class="py-3 pr-3 font-medium">{{ $document->referee?->fullName() ?? '—' }}</td>
                                <td class="py-3 pr-3">{{ ucfirst(str_replace('_', ' ', $document->type)) }}</td>
                                <td class="py-3 text-right text-amber-700 dark:text-amber-300">{{ $document->expiry_date->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-neutral-500">{{ __('No documents are due to expire soon.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>