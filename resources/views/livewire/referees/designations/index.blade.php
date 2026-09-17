<?php

use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeRole;
use App\Models\Referees\RefereeSeason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?int $leagueFilter = null;

    public ?int $roleFilter = null;

    public string $listFilter = 'all';

    public int $seasonYear = 0;

    public string $competition = RefereeSeason::COMPETITION_LIGUE_1;

    public array $leagues = [];

    public array $roles = [];

    public function mount(): void
    {
        $this->authorize('manage_seasons');

        $today = now();
        $this->seasonYear = $today->month >= 7 ? $today->year : $today->year - 1;
        $this->leagues = League::query()->orderBy('code')->get(['id', 'code', 'name'])->toArray();
        $this->roles = RefereeRole::query()
            ->whereIn('slug', RefereeRole::officiatingSlugs())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLeagueFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingListFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSeasonYear(): void
    {
        $this->resetPage();
    }

    public function updatingCompetition(): void
    {
        $this->resetPage();
    }

    public function toggleDesignation(int $refereeId): void
    {
        $this->authorize('manage_seasons');
        $this->resetValidation('designation');

        validator([
            'season_year' => $this->seasonYear,
            'competition' => $this->competition,
        ], [
            'season_year' => ['required', 'integer', 'between:2000,2100'],
            'competition' => ['required', Rule::in(RefereeSeason::competitions())],
        ])->validate();

        DB::transaction(function () use ($refereeId): void {
            $referee = Referee::query()
                ->with('refereeRole')
                ->lockForUpdate()
                ->findOrFail($refereeId);

            $designation = RefereeSeason::query()
                ->where('referee_id', $referee->id)
                ->where('season_year', $this->seasonYear)
                ->where('competition', $this->competition)
                ->lockForUpdate()
                ->first();

            if ($designation?->status === 'active') {
                $designation->update(['status' => 'inactive']);
                session()->flash('status', __('The referee was removed from the seasonal list.'));

                return;
            }

            if (! $referee->isEligibleForSeason($this->seasonYear)) {
                throw ValidationException::withMessages([
                    'designation' => __('Only active referees with valid medical and physical clearance can be designated.'),
                ]);
            }

            $values = [
                'status' => 'active',
                'designated_by' => auth()->id(),
                'designated_at' => now(),
            ];

            if ($designation) {
                $designation->update($values);
            } else {
                RefereeSeason::create([
                    'referee_id' => $referee->id,
                    'season_year' => $this->seasonYear,
                    'competition' => $this->competition,
                    ...$values,
                ]);
            }

            session()->flash('status', __('The referee was added to the seasonal list.'));
        });
    }

    public function with(): array
    {
        $seasonConstraint = fn ($query) => $query
            ->where('season_year', $this->seasonYear)
            ->where('competition', $this->competition)
            ->where('status', 'active');

        $query = Referee::query()
            ->with([
                'league',
                'refereeCategory',
                'refereeRole',
                'seasons' => $seasonConstraint,
                'medicalExams' => fn ($query) => $query
                    ->where('season_year', $this->seasonYear),
                'physicalTests' => fn ($query) => $query
                    ->where('season_year', $this->seasonYear),
            ])
            ->whereHas('refereeRole', fn (Builder $roleQuery) => $roleQuery
                ->whereIn('slug', RefereeRole::officiatingSlugs()))
            ->when(filled($this->search), function (Builder $query): void {
                $search = '%'.trim($this->search).'%';

                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('last_name', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('person_id', 'like', $search);
                });
            })
            ->when($this->leagueFilter, fn (Builder $query, int $leagueId) => $query
                ->where('league_id', $leagueId))
            ->when($this->roleFilter, fn (Builder $query, int $roleId) => $query
                ->where('referee_role_id', $roleId))
            ->when($this->listFilter === 'designated', fn (Builder $query) => $query
                ->whereHas('seasons', $seasonConstraint))
            ->when($this->listFilter === 'eligible', fn (Builder $query) => $query
                ->eligibleForSeason($this->seasonYear))
            ->orderBy('last_name')
            ->orderBy('first_name');

        $designations = RefereeSeason::query()
            ->where('season_year', $this->seasonYear)
            ->where('competition', $this->competition)
            ->where('status', 'active');

        $centralCount = (clone $designations)
            ->whereHas('referee.refereeRole', fn (Builder $query) => $query
                ->where('slug', RefereeRole::CENTRAL_SLUG))
            ->count();
        $assistantCount = (clone $designations)
            ->whereHas('referee.refereeRole', fn (Builder $query) => $query
                ->where('slug', RefereeRole::ASSISTANT_SLUG))
            ->count();

        $currentStartYear = now()->month >= 7 ? now()->year : now()->year - 1;
        $seasonOptions = collect(range($currentStartYear + 2, $currentStartYear - 5))
            ->mapWithKeys(fn (int $year) => [$year => $year.'–'.($year + 1)])
            ->all();

        return [
            'referees' => $query->paginate(15),
            'seasonOptions' => $seasonOptions,
            'competitionLabel' => RefereeSeason::competitionLabel($this->competition),
            'totalDesignated' => $centralCount + $assistantCount,
            'centralCount' => $centralCount,
            'assistantCount' => $assistantCount,
        ];
    }
};

?>

<section class="container mx-auto h-full w-full max-w-7xl px-4 py-6 sm:px-6">
    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Season designations') }}</flux:heading>
            <flux:subheading>
                {{ __('Select the referees and assistant referees authorized to officiate in Ligue 1 and Ligue 2.') }}
            </flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="blue" size="lg">
                {{ $competitionLabel }} · {{ $seasonYear }}–{{ $seasonYear + 1 }}
            </flux:badge>
            @can('export_referee_data')
                <flux:button variant="outline" icon="arrow-down-tray"
                    :href="route('referees.eligible.export', ['season' => $seasonYear])">
                    {{ __('Export eligible referees PDF') }}
                </flux:button>
            @endcan
        </div>
    </div>

    @error('designation')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ $message }}
        </div>
    @enderror

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total designated') }}</div>
            <div class="mt-1 text-2xl font-bold">{{ $totalDesignated }}</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Referees') }}</div>
            <div class="mt-1 text-2xl font-bold">{{ $centralCount }}</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-[#0E1526]">
            <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Assistant referees') }}</div>
            <div class="mt-1 text-2xl font-bold">{{ $assistantCount }}</div>
        </div>
    </div>

    <div class="mb-5 grid gap-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-[#0E1526] md:grid-cols-2 xl:grid-cols-6">
        <flux:select wire:model.live="seasonYear" :label="__('Season')">
            @foreach($seasonOptions as $year => $label)
                <flux:select.option value="{{ $year }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="competition" :label="__('Competition')">
            <flux:select.option value="ligue_1">Ligue 1</flux:select.option>
            <flux:select.option value="ligue_2">Ligue 2</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="listFilter" :label="__('List')">
            <flux:select.option value="all">{{ __('All referees') }}</flux:select.option>
            <flux:select.option value="eligible">{{ __('Eligible referees') }}</flux:select.option>
            <flux:select.option value="designated">{{ __('Designated referees') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="leagueFilter" :label="__('League')">
            <flux:select.option value="">{{ __('All leagues') }}</flux:select.option>
            @foreach($leagues as $league)
                <flux:select.option value="{{ $league['id'] }}">
                    {{ $league['code'] }} – {{ $league['name'] }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="roleFilter" :label="__('Function')">
            <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
            @foreach($roles as $role)
                <flux:select.option value="{{ $role['id'] }}">{{ $role['name'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
            :label="__('Search')" :placeholder="__('Name or person ID')" />
    </div>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-[#0E1526]">
        <table class="w-full min-w-[900px] text-left text-sm text-neutral-600 dark:text-neutral-300">
            <thead class="border-b border-neutral-200 text-xs uppercase text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Person ID') }}</th>
                    <th class="px-4 py-3">{{ __('Full name') }}</th>
                    <th class="px-4 py-3">{{ __('League') }}</th>
                    <th class="px-4 py-3">{{ __('Category') }}</th>
                    <th class="px-4 py-3">{{ __('Function') }}</th>
                    <th class="px-4 py-3">{{ __('Eligibility') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Designation') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                @forelse($referees as $referee)
                    @php
                        $designation = $referee->seasons->first();
                        $medicalPassed = $referee->medicalExams->first()?->result === 'passed';
                        $physicalPassed = $referee->physicalTests->first()?->result === 'passed';
                        $eligible = $referee->is_active && $medicalPassed && $physicalPassed;
                    @endphp
                    <tr wire:key="referee-season-{{ $referee->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-900/60">
                        <td class="px-4 py-3 font-mono text-xs">{{ $referee->person_id ?? '—' }}</td>
                        <td class="px-4 py-3 font-semibold text-neutral-900 dark:text-white">
                            @can('view_referee')
                                <a href="{{ route('referees.show', $referee) }}" wire:navigate
                                    class="hover:text-blue-600 hover:underline dark:hover:text-blue-400">
                                    {{ $referee->fullName() }}
                                </a>
                            @else
                                {{ $referee->fullName() }}
                            @endcan
                        </td>
                        <td class="px-4 py-3">{{ $referee->league?->code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $referee->refereeCategory?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $referee->refereeRole?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <flux:badge size="sm" :color="$referee->is_active ? 'green' : 'red'">
                                    {{ $referee->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                                <flux:badge size="sm" :color="$medicalPassed ? 'green' : 'red'">
                                    {{ __('Medical') }}
                                </flux:badge>
                                <flux:badge size="sm" :color="$physicalPassed ? 'green' : 'red'">
                                    {{ __('Physical') }}
                                </flux:badge>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($designation)
                                <flux:button size="sm" variant="danger" class="cursor-pointer"
                                    wire:click="toggleDesignation({{ $referee->id }})"
                                    wire:confirm="{{ __('Remove this referee from the selected list?') }}"
                                    wire:loading.attr="disabled" wire:target="toggleDesignation({{ $referee->id }})">
                                    {{ __('Remove') }}
                                </flux:button>
                            @elseif($eligible)
                                <flux:button size="sm" variant="primary" color="green" class="cursor-pointer"
                                    wire:click="toggleDesignation({{ $referee->id }})"
                                    wire:loading.attr="disabled" wire:target="toggleDesignation({{ $referee->id }})">
                                    {{ __('Designate') }}
                                </flux:button>
                            @else
                                @can('edit_referee')
                                    <flux:button size="sm" variant="outline" class="cursor-pointer"
                                        :href="route('referees.edit', $referee)" wire:navigate>
                                        {{ __('Record aptitudes') }}
                                    </flux:button>
                                @else
                                    <flux:badge color="amber" size="sm">{{ __('Not eligible') }}</flux:badge>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-neutral-500">
                            {{ __('No referee matches these criteria.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $referees->links() }}
    </div>
</section>
