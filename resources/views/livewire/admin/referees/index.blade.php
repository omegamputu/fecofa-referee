<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\League;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeRole;
use App\Models\Referees\RefereeCategory;

new class extends Component {

    use WithPagination;

    public string $search = '';
    public ?int $leagueFilter = null;
    public ?int $categoryFilter = null;
    public ?int $roleFilter = null;

    public array $leagues = [];
    public array $categories = [];
    public array $roles = [];

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

    public function with(): array
    {

        $query = Referee::query()
            ->with(['league', 'refereeCategory', 'refereeRole'])
            // 🔍 recherche texte
            ->when(filled($this->search), function ($q) {
                $search = '%' . $this->search . '%';

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
            ->orderBy('id', 'asc');

        return [
            'referees' => $query->paginate(15),
        ];
    }

    public function toggleMedical(int $id): void
    {
        $referee = Referee::findOrFail($id);
        $referee->has_medical_clearance = !$referee->has_medical_clearance;
        $referee->save();
    }

    public function togglePhysical(int $id): void
    {
        $referee = Referee::findOrFail($id);
        $referee->has_physical_clearance = !$referee->has_physical_clearance;
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

        <flux:button variant="primary" color="green" class="shrink-0 cursor-pointer"
            :href="route('admin.referees.create')" wire:navigate>
            {{ __('Add referee') }}
        </flux:button>

        <a href="{{ route('referees.export', [
    'search' => $search ?? null,
    'league' => $leagueFilter ?? null,
    'role' => $roleFilter ?? null,
]) }}" class="inline-flex items-center rounded-lg bg-white border px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
            {{ __("Export PDF") }}
        </a>
    </div>

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
                <th class="px-4 py-3 text-center">{{ __('Phone number') }}</th>
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
                                    {{ ucfirst($referee->gender ?? '')}},
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

                    {{-- Tests physiques --}}
                    <td class="px-4 py-3 text-center">
                        {{ $referee->phone ?? '-' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3 text-center">
                        <flux:button size="xs" variant="ghost"
                            class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]"
                            :href="route('admin.referees.edit', $referee)" wire:navigate>
                            {{ __('Edit') }}
                        </flux:button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-neutral-400">
                        {{ __('No referees found.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $referees->links() }}
    </div>
</section>