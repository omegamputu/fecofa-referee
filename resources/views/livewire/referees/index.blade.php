<?php

use App\Actions\ImportRefereesFromCsv;
use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?int $leagueFilter = null;

    public ?int $categoryFilter = null;

    public ?int $roleFilter = null;

    public array $leagues = [];

    public array $categories = [];

    public array $roles = [];

    public $csvFile = null;

    public bool $showCsvImport = false;

    public ?array $csvImportSummary = null;

    public array $csvImportErrors = [];

    public function mount(): void
    {
        $this->leagues = League::orderBy('code')->get(['id', 'code', 'name'])->toArray();
        $this->categories = RefereeCategory::orderBy('id')->get(['id', 'name'])->toArray();
        $this->roles = RefereeRole::orderBy('name')->get(['id', 'name'])->toArray();
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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function importCsv(ImportRefereesFromCsv $importer): void
    {
        $this->authorize('import_referee_data');

        $this->csvImportSummary = null;
        $this->csvImportErrors = [];

        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $importer->handle($this->csvFile);

        $this->csvImportSummary = [
            'imported' => $result['imported'],
            'rejected' => $result['rejected'],
        ];
        $this->csvImportErrors = $result['errors'];
        $this->reset('csvFile');
        $this->resetPage();

        session()->flash('status', __('CSV import completed: :imported imported, :rejected rejected.', [
            'imported' => $result['imported'],
            'rejected' => $result['rejected'],
        ]));
    }

    public function with(): array
    {

        $query = Referee::query()
            ->with(['league', 'refereeCategory', 'refereeRole'])
            // 🔍 recherche texte
            ->when(filled($this->search), function ($q) {
                $search = '%'.$this->search.'%';

                $q->where(function ($sub) use ($search) {
                    $sub->where('last_name', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('person_id', 'like', $search);
                });
            })
            // 🎯 filtre Ligue (id numérique)
            ->when(filled($this->leagueFilter), function ($q) {
                $q->where('league_id', $this->leagueFilter);
            })
            // 🎯 filtre Fonction
            ->when(filled($this->roleFilter), function ($q) {
                $q->where('referee_role_id', $this->roleFilter);
            })
            // 🎯 filtre Category
            ->when(filled($this->categoryFilter), function ($q) {
                $q->where('referee_category_id', $this->categoryFilter);
            })
            ->orderBy('id', 'desc');

        return [
            'referees' => $query->paginate(15),
        ];
    }

    public function toggleMedical(int $id): void
    {
        $this->authorize('edit_referee');

        $referee = Referee::findOrFail($id);
        $referee->has_medical_clearance = ! $referee->has_medical_clearance;
        $referee->save();
    }

    public function togglePhysical(int $id): void
    {
        $this->authorize('edit_referee');

        $referee = Referee::findOrFail($id);
        $referee->has_physical_clearance = ! $referee->has_physical_clearance;
        $referee->save();
    }
}

?>

<section class="container mx-auto h-full w-full max-w-7xl px-6">
    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

    <div class="flex items-center justify-between mb-6 gap-4">
        <div class="flex flex-1 gap-2">
            <flux:input class="w-full" icon="magnifying-glass"
                placeholder="{{ __('Search by name, category or code') }}" wire:model.live.debounce.400ms="search" />

            <flux:select wire:model="leagueFilter" placeholder="{{ __('League') }}" class="w-48">
                <flux:select.option value="">{{ __('All leagues') }}</flux:select.option>
                @foreach($leagues as $league)
                    <flux:select.option value="{{ $league['id'] }}">
                        {{ $league['code'] }} – {{ $league['name'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="categoryFilter" placeholder="{{ __('Category') }}" class="w-48">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach($categories as $category)
                    <flux:select.option value="{{ $category['id'] }}">
                        {{ $category['name'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="roleFilter" placeholder="{{ __('Function') }}" class="w-48">
                <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
                @foreach($roles as $role)
                    <flux:select.option value="{{ $role['id'] }}">
                        {{ $role['name'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
            @can('import_referee_data')
                <flux:button variant="outline" class="cursor-pointer" wire:click="$toggle('showCsvImport')">
                    {{ __('Import CSV') }}
                </flux:button>
            @endcan

            @can('create_referee')
                <flux:button variant="primary" color="green" class="cursor-pointer" :href="route('referees.create')"
                    wire:navigate>
                    {{ __('Add referee') }}
                </flux:button>
            @endcan

            @can('export_referee_data')
                <a href="{{ route('referees.export', [
                'search' => $search ?? null,
                'league' => $leagueFilter ?? null,
                'role' => $roleFilter ?? null,
            ]) }}"
                    class="inline-flex items-center rounded-lg bg-white border px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                    {{ __("Export PDF") }}
                </a>
            @endcan
        </div>
    </div>

    @can('import_referee_data')
        @if ($showCsvImport)
            <section class="mb-6 rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-[#0E1526]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ __('Import referees from CSV') }}</h2>
                        <p class="mt-1 max-w-3xl text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('Use the template and keep the required columns. Existing leagues, categories and functions must be used.') }}
                        </p>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            {{ __('Required columns: nom, prenoms, sexe, code_ligue, categorie, fonction. Maximum :max referees and 5 MB.', ['max' => ImportRefereesFromCsv::MAX_ROWS]) }}
                        </p>
                    </div>

                    <a href="{{ route('referees.import.template') }}"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-600 dark:text-neutral-200 dark:hover:bg-neutral-800">
                        {{ __('Download CSV template') }}
                    </a>
                </div>

                <form wire:submit="importCsv" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="w-full">
                        <flux:input type="file" wire:model="csvFile" label="{{ __('CSV file') }}"
                            accept=".csv,text/csv,text/plain" />
                        @error('csvFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <flux:button type="submit" variant="primary" color="green" class="shrink-0 cursor-pointer"
                        wire:loading.attr="disabled" wire:target="csvFile,importCsv">
                        <span wire:loading.remove wire:target="csvFile,importCsv">{{ __('Import') }}</span>
                        <span wire:loading wire:target="csvFile,importCsv">{{ __('Importing...') }}</span>
                    </flux:button>
                </form>

                @if ($csvImportSummary)
                    <div class="mt-5 rounded-lg border border-neutral-200 bg-neutral-50 p-4 text-sm dark:border-neutral-700 dark:bg-neutral-900/40">
                        <p class="font-medium text-neutral-900 dark:text-white">
                            {{ __('CSV import completed: :imported imported, :rejected rejected.', $csvImportSummary) }}
                        </p>

                        @if ($csvImportErrors !== [])
                            <ul class="mt-3 max-h-48 list-disc space-y-1 overflow-y-auto pl-5 text-red-700 dark:text-red-300">
                                @foreach ($csvImportErrors as $importError)
                                    <li>{{ $importError }}</li>
                                @endforeach
                            </ul>

                            @if ($csvImportSummary['rejected'] > count($csvImportErrors))
                                <p class="mt-2 text-xs text-neutral-500">
                                    {{ __('Only the first :count errors are displayed.', ['count' => count($csvImportErrors)]) }}
                                </p>
                            @endif
                        @endif
                    </div>
                @endif
            </section>
        @endif
    @endcan

    <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl">
        <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-slate-400 bg-white dark:bg-[#0E1526] py-6 px-6 rounded-xl">
            <thead class="text-xs text-gray-700 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Person ID') }}</th>
                    <th class="px-4 py-3">{{ __('Full name') }}</th>
                    <th class="px-4 py-3">{{ __('League') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Category') }}</th>
                    <th class="px-4 py-3">{{ __('Function') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($referees as $referee)
                    <tr class="dark:text-slate-400">
                        <td class="px-4 py-3">
                            {{ $referee->person_id }}
                        </td>
                        {{-- Colonne arbitre --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($referee->profile_photo_path)
                                    <img src="{{ asset('storage/' . $referee->profile_photo_path) }}" alt="Referee photo"
                                        class="h-9 w-9 shrink-0 overflow-hidden rounded-full object-cover">
                                @else

                                    <span
                                        class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-full bg-neutral-700 text-xs font-semibold items-center justify-center">
                                        {{ strtoupper(Str::substr($referee->first_name, 0, 1) . Str::substr($referee->last_name, 0, 1)) }}
                                    </span>

                                @endif

                                <div>
                                    <div class="font-semibold">
                                        {{ $referee->last_name }} {{ $referee->first_name }}
                                    </div>
                                    <div class="text-xs text-neutral-400">
                                        {{ ucfirst(__($referee->gender) ?? '')}},
                                        {{ optional($referee->date_of_birth)->format('d/m/Y') }}
                                        – {{ $referee->refereeRole?->name }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Ligue --}}
                        <td class="px-4 py-3">
                            {{ $referee->league?->code }}
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            @if ($referee->is_active)
                                <flux:badge color="green" size="sm" class="dark:text-white dark:bg-green-500">{{ __("Active") }}
                                </flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __("Inactive") }}</flux:badge>
                            @endif
                        </td>

                        {{-- Category --}}
                        <td class="px-4 py-3 text-center">
                            {{ ucfirst($referee->refereeCategory?->name) }}
                        </td>

                        {{-- Fonction --}}
                        <td class="px-4 py-3">
                            {{ $referee->refereeRole?->name }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-1">
                                <flux:button size="xs" variant="ghost"
                                    class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]"
                                    :href="route('referees.show', $referee)" wire:navigate>
                                    {{ __('View profile') }}
                                </flux:button>

                                @can('edit_referee')
                                    <flux:button size="xs" variant="ghost"
                                        class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]"
                                        :href="route('referees.edit', $referee)" wire:navigate>
                                        {{ __('Edit') }}
                                    </flux:button>
                                @endcan

                                @can('manage_seasons')
                                    <flux:button size="xs" variant="ghost"
                                        class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]"
                                        :href="route('referees.designations.index', ['search' => $referee->person_id ?: $referee->fullName()])"
                                        wire:navigate>
                                        {{ __('Designate') }}
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-neutral-400">
                            {{ __('No referees found.') }}
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
