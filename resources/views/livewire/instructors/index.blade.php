<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Referees\RefereeCategory;
use App\Models\Referees\RefereeRole;
use App\Models\Instructors\InstructorRole;
use App\Models\Instructors\Instructor;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $RefereeRoleFilter = null;
    public ?int $RefereeCategoryFilter = null;
    public ?int $InstructorRoleFilter = null;

    public array $categories = [];
    public array $roles = [];
    public array $instructors_roles = [];

    public function mount(): void
    {
        $this->roles = RefereeRole::orderBy('name')->get(['id', 'name'])->toArray();
        $this->instructors_roles = InstructorRole::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Instructor::query()
            ->with(['instructorRole'])
            // 🔍 recherche texte
            ->when(filled($this->search), function ($q) {
                $search = '%' . $this->search . '%';

                $q->where(function ($sub) use ($search) {
                    $sub->where('last_name', 'like', $search)
                        ->orWhere('first_name', 'like', $search);
                });
            })
            // 🎯 filtre Instructor Role (id numérique)
            ->when(filled($this->InstructorRoleFilter), function ($q) {
                $q->where('instructor_role_id', $this->InstructorRoleFilter);
            })
            // 🎯 filtre Fonction
            ->when(filled($this->RefereeRoleFilter), function ($q) {
                $q->where('referee_role_id', $this->RefereeRoleFilter);
            })
            ->orderBy('id', 'asc');

        return [
            'instructors' => $query->paginate(15),
        ];
    }
}

?>

<section class="container mx-auto h-full w-full max-w-7xl px-6">
    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

    <div class="flex items-center justify-between mb-6 gap-4">
        <div class="flex flex-1 gap-2">
            <flux:input class="w-full" icon="magnifying-glass"
                placeholder="{{ __('Search by name, category or role') }}" wire:model.live.debounce.400ms="search" />

            <flux:select wire:model="InstructorRoleFilter" class="w-48">
                <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
                @foreach($instructors_roles as $item)
                    <flux:select.option value="{{ $item['id'] }}">
                        {{ $item['name'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="roleFilter" class="w-48">
                <flux:select.option value="">{{ __('All functions') }}</flux:select.option>
                @foreach($roles as $role)
                    <flux:select.option value="{{ $role['id'] }}">
                        {{ $role['name'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @can('create_referee')
            <flux:button variant="primary" color="green" class="shrink-0 cursor-pointer" :href="route('instructors.create')"
                wire:navigate>
                {{ __('Add instructor') }}
            </flux:button>
        @endcan

        @can('export_referee_data')
                <a href="{{ route('instructors.export', [
                'search' => $search ?? null,
                'league' => $leagueFilter ?? null,
                'role' => $roleFilter ?? null,
            ]) }}"
                    class="inline-flex items-center rounded-lg bg-white border px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50">
                    {{ __("Export PDF") }}
                </a>
        @endcan
    </div>

    <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl">
        <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-slate-400 bg-white dark:bg-[#0E1526] py-6 px-6 rounded-xl">
            <thead class="text-xs text-gray-700 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3">{{ __('ID') }}</th>
                    <th class="px-4 py-3">{{ __('Full name') }}</th>
                    <th class="px-4 py-3">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Mobile number') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Instructor since') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Role') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($instructors as $instructor)
                    <tr class="dark:text-slate-400">
                        <td class="px-4 py-3">
                            {{ $instructor->id }}
                        </td>
                        {{-- Colonne arbitre --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($instructor->profile_photo_path)
                                    <img src="{{ asset('storage/' . $instructor->profile_photo_path) }}" alt="Referee photo"
                                        class="h-9 w-9 shrink-0 overflow-hidden rounded-full object-cover">
                                @else

                                    <span
                                        class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-full bg-neutral-700 text-xs font-semibold items-center justify-center">
                                        {{ strtoupper(Str::substr($instructor->first_name, 0, 1) . Str::substr($instructor->last_name, 0, 1)) }}
                                    </span>

                                @endif

                                <div>
                                    <div class="font-semibold">
                                        {{ $instructor->last_name }} {{ $instructor->first_name }}
                                    </div>
                                    <div class="text-xs text-neutral-400">
                                        {{ ucfirst($instructor->gender ?? '')}},
                                        {{ $instructor->year_of_birth  }}
                                        – {{ $instructor->instructorRole?->name }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Email --}}
                        <td class="px-4 py-3">
                            {{ $instructor->email }}
                        </td>

                        {{-- Phone --}}
                        <td class="px-4 py-3 text-center">
                            {{ $instructor->phone ?? '-' }}
                        </td>

                        {{-- Category --}}
                        <td class="px-4 py-3 text-center">
                            {{ ucfirst($instructor->start_year) }}
                        </td>

                        {{-- Fonction --}}
                        <td class="px-4 py-3">
                            {{ $instructor->instructorRole?->name }}
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            @if ($instructor->is_active)
                                <flux:badge color="green" size="sm" class="dark:text-white dark:bg-green-500">{{ __("Active") }}
                                </flux:badge>
                            @else
                                <flux:badge color="red" size="sm">{{ __("Inactive") }}</flux:badge>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-center">
                            @can('edit_referee')
                                <flux:button size="xs" variant="ghost"
                                    class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]"
                                    :href="route('instructors.edit', $instructor)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-neutral-400">
                            {{ __('No instructor found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $instructors->links() }}
    </div>
</section>