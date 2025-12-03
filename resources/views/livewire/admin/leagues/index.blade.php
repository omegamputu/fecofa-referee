<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\League;
use App\Actions\Leagues\CreateLeague;
use App\Actions\Leagues\EditLeague;
use App\Actions\Leagues\UpdateLeague;
use App\Actions\Leagues\DeleteLeague;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    // Create
    public string $name = '';
    public ?string $code = null;
    public ?string $province = null;
    public ?string $headquarters = null;
    public ?string $contact_email = null;
    public ?string $contact_phone = null;

    // Edit
    public ?int $editLeagueId = null;
    public string $editName = '';
    public ?string $editCode = null;
    public ?string $editProvince = null;
    public ?string $editHeadquarters = null;
    public ?string $editContactEmail = null;
    public ?string $editContactPhone = null;

    protected $listeners = ['leagues:refresh' => '$refresh'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'leagues' => League::query()
                ->when($this->search, function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('province', 'like', "%{$this->search}%");
                })
                ->orderBy('id', 'desc')
                ->paginate($this->perPage),
        ];
    }

    /* ---------- Validation ---------- */

    protected function createRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:leagues,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:leagues,code'],
            'province' => ['nullable', 'string', 'max:255'],
            'headquarters' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function updateRules(): array
    {
        return [
            'editName' => ['required', 'string', 'max:255', 'unique:leagues,name,' . $this->editLeagueId],
            'editCode' => ['nullable', 'string', 'max:50', 'unique:leagues,code,' . $this->editLeagueId],
            'editProvince' => ['nullable', 'string', 'max:255'],
            'editHeadquarters' => ['nullable', 'string', 'max:255'],
            'editContactEmail' => ['nullable', 'email', 'max:255'],
            'editContactPhone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /* ---------- Actions ---------- */

    public function createLeague(): void
    {
        $this->authorize('manage', League::class);

        $validated = $this->validate($this->createRules());

        app(CreateLeague::class)($validated);

        $this->reset(['name', 'province', 'headquarters', 'contact_email', 'contact_phone']);
        session()->flash('message', __('League created successfully.'));

        $this->resetPage();
        $this->dispatch('leagues:refresh');
    }

    public function editLeague(int $id): void
    {
        $this->authorize('manage', League::class);

        $league = League::findOrFail($id);

        $this->editLeagueId = $league->id;
        $this->editName = $league->name;
        $this->editCode = $league->code;
        $this->editProvince = $league->province;
        $this->editHeadquarters = $league->headquarters;
        $this->editContactEmail = $league->contact_email;
        $this->editContactPhone = $league->contact_phone;
    }

    public function updateLeague(): void
    {
        $this->authorize('manage', League::class);

        $this->validate($this->updateRules());

        $league = League::findOrFail($this->editLeagueId);

        app(UpdateLeague::class)($league, [
            'name' => $this->editName,
            'code' => $this->editCode,
            'province' => $this->editProvince,
            'headquarters' => $this->editHeadquarters,
            'contact_email' => $this->editContactEmail,
            'contact_phone' => $this->editContactPhone,
        ]);

        session()->flash('status', __('League updated successfully.'));
        $this->dispatch('leagues:refresh');
    }

    public function deleteLeague(int $id): void
    {
        $this->authorize('manage', League::class);

        $league = League::findOrFail($id);

        app(DeleteLeague::class)($league);

        session()->flash('message', __('League deleted successfully.'));
        $this->resetPage();
        $this->dispatch('leagues:refresh');
    }

};

?>

<div>
    <section class="container mx-auto w-full max-w-5xl">

        <x-auth-session-status class="text-center" :status="session('status')" />

        <h1 class="text-2xl font-semibold dark:text-neutral-400 mb-4">{{ __("Leagues") }}</h1>

        <div class="flex items-center justify-between mb-4">
            <div>
                <label for="default-search" class="sr-only">{{ __("Search") }}</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>

                    <input type="text" wire:model.debounce.400ms="search" id="default-search"
                        placeholder="{{ __('Search by name') }}" class="block w-full p-3 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg
                        bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>
            </div>

            <flux:modal.trigger name="create-league">
                <flux:button variant="primary" color="green" class="cursor-pointer">{{ __("Add league") }}
                </flux:button>
            </flux:modal.trigger>
        </div>

        <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 py-6 px-6 rounded-xl">
                <thead class="text-xs text-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">{{ __("Name") }}</th>
                        <th class="px-6 py-3">{{ __("Code") }}</th>
                        <th class="px-6 py-3">{{ __("Province") }}</th>
                        <th class="px-6 py-3">{{ __("Headquarters") }}</th>
                        <th class="px-6 py-3">{{ __("Contact") }}</th>
                        <th class="px-6 py-3">{{ __("Actions") }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($leagues as $league)
                        <tr>
                            <td class="px-6 py-4">{{ $league->name }}</td>
                            <td class="px-6 py-4">{{ $league->code }}</td>
                            <td class="px-6 py-4">{{ $league->province }}</td>
                            <td class="px-6 py-4">{{ $league->headquarters }}</td>
                            <td class="px-6 py-4">
                                {{ $league->contact_email }}<br>
                                {{ $league->contact_phone }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">

                                    <flux:modal.trigger name="edit-league-{{ $league->id }}">
                                        <flux:button wire:click="editLeague({{ $league->id }})" size="sm"
                                            class="cursor-pointer">
                                            {{ __("Edit") }}
                                        </flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal.trigger name="delete-league-{{ $league->id }}">
                                        <flux:button variant="danger" size="sm" class="cursor-pointer">
                                            {{ __("Delete") }}
                                        </flux:button>
                                    </flux:modal.trigger>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $leagues->links() }}
        </div>

        {{-- Modal Create --}}
        <flux:modal name="create-league" class="md:w-96" variant="flyout">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __("Create league") }}</flux:heading>
                <flux:text>{{ __("Describe league details.") }}</flux:text>

                <flux:input label="{{ __('Name') }}" wire:model.defer="name" />
                <flux:input label="Code" wire:model.defer="code" />
                <flux:input label="Province" wire:model.defer="province" />
                <flux:input label="{{ __('Headquarters') }}" wire:model.defer="headquarters" />
                <flux:input label="{{ __('Contact email') }}" wire:model.defer="contact_email" />
                <flux:input label="{{ __('Contact phone') }}" wire:model.defer="contact_phone" />

                <flux:modal.close>
                    <flux:button wire:click="createLeague" class="w-full" variant="primary">
                        {{ __("Save") }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </flux:modal>

        {{-- Modal Edit --}}
        @foreach($leagues as $league)
            <flux:modal name="edit-league-{{ $league->id }}" class="md:w-96" wire:key="edit-league-{{ $league->id }}"
                variant="flyout">
                <div class="space-y-4">
                    <flux:heading size="lg">{{ __("Edit league") }}</flux:heading>
                    <flux:text>{{ __("Update league details.") }}</flux:text>

                    <flux:input label="{{ __('Name') }}" wire:model.defer="editName" />
                    <flux:input label="Code" wire:model.defer="editCode" />
                    <flux:input label="Province" wire:model.defer="editProvince" />
                    <flux:input label="{{ __('Headquarters') }}" wire:model.defer="editHeadquarters" />
                    <flux:input label="{{ __('Contact email') }}" wire:model.defer="editContactEmail" />
                    <flux:input label="{{ __('Contact phone') }}" wire:model.defer="editContactPhone" />

                    <flux:modal.close>
                        <flux:button wire:click="updateLeague" class="w-full" variant="primary">
                            {{ __("Update") }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </flux:modal>

            <flux:modal name="delete-league-{{ $league->id }}" class="md:w-96">
                <div class="space-y-4">
                    <flux:heading size="lg">{{ __("Delete league") }}</flux:heading>
                    <flux:text>{{ __("Are you sure you want to delete this league? This action cannot be undone.") }}
                    </flux:text>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __("Cancel") }}</flux:button>
                        </flux:modal.close>

                        <flux:modal.close>
                            <flux:button wire:click="deleteLeague({{ $league->id }})" variant="danger">
                                {{ __("Confirm") }}
                            </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        @endforeach
    </section>
</div>