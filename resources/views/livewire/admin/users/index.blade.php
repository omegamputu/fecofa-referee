<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use App\Queries\UserIndexQuery;
use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Actions\Users\ToggleActive;
use App\Actions\Users\ResendInvite;
use App\Actions\Users\DeleteUser;

new class extends Component {
    //
    use WithPagination;

    /* ─────────────
     |  State
     * ────────────*/
    public string $search = '';
    public int $perPage = 10;

    // Create
    public string $name = '';
    public string $email = '';
    public string $role = '';
    public array $roles = [];

    // Edit
    public ?int $editUserId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $selectedRole = '';

    protected $listeners = ['users:refresh' => '$refresh'];

    /* ─────────────
     |  Lifecycle
     * ────────────
    */
    public function mount(): void
    {
        // Cache les rôles non Owner pour éviter la requête à chaque chargement
        $this->roles = cache()->remember(
            'roles:list:non_owner',
            now()->addMinutes(10),
            fn() => Role::whereNotIn('name', ['Owner'])->pluck('name')->toArray()
        );
    }


    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'users' => app(UserIndexQuery::class)->run($this->search, $this->perPage),
        ];
    }

    /* ─────────────
     |  Validation
     * ────────────*/
    protected function createRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'regex:/@fecofa\.cd$/i',
            ],
            'role' => ['required', 'string', 'in:' . implode(',', $this->roles)],
        ];
    }

    protected function updateRules(): array
    {
        return [
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $this->editUserId,
                'regex:/@fecofa\.cd$/i',
            ],
            'selectedRole' => ['required', 'string', 'in:' . implode(',', $this->roles)],
        ];
    }

    /* ─────────────
     |  Actions CRUD
     * ────────────*/

    public function createUser(CreateUser $createUser): void
    {
        $this->authorize('manage', User::class);

        $validated = $this->validate($this->createRules());

        $createUser($validated, $this->role);

        $this->reset(['name', 'email', 'role']);
        session()->flash('message', __('User created and invitation sent.'));
        $this->resetPage();
    }

    public function editUser(int $id): void
    {
        $this->authorize('manage', User::class);

        $user = User::findOrFail($id);

        $this->editUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->selectedRole = $user->roles->pluck('name')->first() ?? '';
    }

    public function updateUser(UpdateUser $updateUser): void
    {
        $this->authorize('manage', User::class);
        $this->validate($this->updateRules());

        $user = User::findOrFail($this->editUserId);

        $updateUser($user, [
            'name' => $this->editName,
            'email' => $this->editEmail,
        ], $this->selectedRole);

        session()->flash('status', __('User updated successfully.'));
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        // Vérifie la policy
        $this->authorize('toggle', $user);

        // Appelle l'action manuellement via le conteneur
        app(ToggleActive::class)($user);

        session()->flash('status', __('User status updated.'));
    }

    public function resendInvitation(int $id, ResendInvite $resendInvite): void
    {
        $user = User::findOrFail($id);
        $this->authorize('invite', $user);

        $resendInvite($user);

        session()->flash('status', __('Invitation sent again to :email', ['email' => $user->email]));
    }

    public function delete(int $id, DeleteUser $deleteUser): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        $deleteUser($user);

        session()->flash('status', __('User deleted successfully.'));
        $this->resetPage();
    }
}; 

?>

<section class="container mx-auto w-full max-w-7xl bg-white py-6 px-6 rounded-3xl">
    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <h1 class="text-2xl font-semibold mb-4">{{ __("Users") }}</h1>

    <div class="flex items-center justify-between mb-4">
        <div>
            <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">
                {{ __("Search") }}
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                    </svg>
                </div>
                <input type="text" id="default-search" wire:model.debounce.300ms="search"
                    class="block w-full p-3 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="{{ __("Search by name") }}" required />
            </div>
        </div>

        <div>
            <flux:modal.trigger name="create-user">
                <flux:button variant="primary" class="cursor-pointer" wire:navigate>
                    {{ __("Add user") }}
                </flux:button>
            </flux:modal.trigger>

            <flux:modal name="create-user" class="md:w-96" variant="flyout">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __("Create user") }}</flux:heading>
                        <flux:text class="mt-2">{{ __("Decribe personal details.") }}</flux:text>
                    </div>

                    <flux:input label="{{ __('Name') }}" wire:model.defer="name" type="text"
                        placeholder="{{ __('Your name') }}" required />
                    <flux:input label="{{ __('Email') }}" wire:model.defer="email" type="email"
                        placeholder="{{ __('Your email') }}" required />

                    <flux:select wire:model="role" label="{{ __('Role') }}" placeholder="{{ __('Select role') }}"
                        required>
                        @foreach ($roles as $role)
                            <flux:select.option class="text-zinc-400" value="{{ $role }}">{{ ucfirst($role) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button wire:click="createUser" type="button" variant="primary" color="green"
                        class="w-full cursor-pointer">
                        {{ __("Invite user") }}
                    </flux:button>
                </div>
            </flux:modal>
        </div>
    </div>

    <table
        class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 bg-white py-6 px-6 rounded-xl">
        <thead class="text-xs text-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    {{ __("Name") }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ __("Email") }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ __("Roles") }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ __("Status") }}
                </th>
                <th scope="col" class="px-6 py-3">
                    {{ __("Actions") }}
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $u)
                <tr>
                    <th scope="row" class="flex items-center px-6 py-4 text-gray-900 whitespace-nowrap dark:text-white">
                        <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                            <span
                                class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                {{ $u->initials() }}
                            </span>
                        </span>
                        <div class="ps-3">
                            <div class="text-base font-semibold">{{ $u->name }}</div>
                            <div class="font-normal text-gray-500">{{ $u->email }}</div>
                        </div>
                    </th>
                    <td class="px-6 py-4">
                        {{ $u->email }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $u->getRoleNames()->join(', ') }}
                    </td>
                    <td class="px-6 py-4">
                        @if ($u->is_active)
                            <flux:badge color="green">{{ __("Active") }}</flux:badge>
                        @else
                            <flux:badge color="red">{{ __("Inactive") }}</flux:badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <flux:modal.trigger name="edit-user-{{ $u->id }}">
                            <flux:button wire:click="editUser({{ $u->id }})" :loading="false" size="sm"
                                class="cursor-pointer">
                                {{ __("Edit") }}
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="edit-user-{{ $u->id }}" class="md:w-96" wire:key="edit-user-modal-{{ $u->id }}"
                            variant="flyout">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">{{ __("Edit user") }}</flux:heading>
                                    <flux:text class="mt-2">{{ __("Update personal details.") }}</flux:text>
                                </div>

                                <flux:input label="{{ __('Name') }}" wire:model.defer="editName" type="text"
                                    placeholder="Your name" />
                                <flux:input label="{{ __('Email') }}" wire:model.defer="editEmail" type="email"
                                    placeholder="Your email" />

                                <flux:select wire:model="selectedRole" label="Role" placeholder="{{ __('Select role') }}">
                                    @foreach ($roles as $role)
                                        <flux:select.option class="text-zinc-400" value="{{ $role }}">{{ ucfirst($role) }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <div class="flex gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost" type="button" class="cursor-pointer">
                                            {{ __("Cancel") }}
                                        </flux:button>
                                    </flux:modal.close>

                                    <flux:modal.close>
                                        <flux:button wire:click="updateUser" type="button" variant="primary" color="green"
                                            class="cursor-pointer">
                                            {{ __("Update") }}
                                        </flux:button>
                                    </flux:modal.close>
                                </div>

                                <flux:separator text="or" />

                                <div class="text-sm text-zinc-500 dark:text-zinc-300">
                                    <span class="font-semibold mb-3"> {{ __("Invitation Sent") }} </span> :
                                    {{ $u->invitation_sent_count ?? 0 }} </br>

                                    @if($u->invited_at)
                                        <span class="font-semibold">{{ __("Last Sent") }}</span> :
                                        {{ optional($u->invited_at)->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                            </div>
                        </flux:modal>

                        <flux:modal.trigger name="resent-invitation-user-{{ $u->id }}">
                            <flux:button variant="primary" color="blue" :loading="false" size="sm"
                                class="ms-1 cursor-pointer">
                                {{ __("Resend Invitation") }}
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="resent-invitation-user-{{ $u->id }}" class="md:w-96">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">{{ __("Resend Invitation") }}</flux:heading>
                                    <flux:text class="mt-2">
                                        {{ __("Are you sure you want to resend the invitation to this user?") }}
                                    </flux:text>
                                </div>

                                <div class="flex gap-2">
                                    <flux:spacer />

                                    <flux:modal.close>
                                        <flux:button variant="ghost" type="button" class="cursor-pointer">
                                            {{ __("Cancel") }}
                                        </flux:button>
                                    </flux:modal.close>

                                    <flux:modal.close>
                                        <flux:button wire:click="resendInvitation({{ $u->id }})" type="button"
                                            variant="primary" color="green" class="cursor-pointer">
                                            {{ __("Confirm") }}
                                        </flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>

                        <flux:modal.trigger name="toggle-active-user-{{ $u->id }}">
                            <flux:button variant="{{ $u->is_active ? 'primary' : 'primary' }}"
                                color="{{ $u->is_active ? 'orange' : 'emerald' }}" :loading="false" size="sm"
                                class="cursor-pointer ms-1">
                                @if ($u->is_active)
                                    {{ __("Deactivate") }}
                                @else
                                    {{ __("Activate") }}
                                @endif
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="toggle-active-user-{{ $u->id }}" class="md:w-96">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">{{ $u->is_active ? 'Deactivate' : 'Activate' }} user
                                    </flux:heading>
                                    <flux:text class="mt-2">Are you sure you want to
                                        {{ $u->is_active ? 'deactivate' : 'activate' }} this user?
                                    </flux:text>
                                </div>

                                <div class="flex gap-2">
                                    <flux:spacer />

                                    <flux:modal.close>
                                        <flux:button variant="ghost" type="button" class="cursor-pointer">
                                            {{ __("Cancel") }}
                                        </flux:button>
                                    </flux:modal.close>

                                    <flux:modal.close>
                                        <flux:button wire:click="toggleActive({{ $u->id }})" type="button" variant="primary"
                                            color="green" class="cursor-pointer">
                                            {{ __("Confirm") }}
                                        </flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>

                        <flux:modal.trigger name="delete-user-{{ $u->id }}">
                            <flux:button variant="danger" :loading="false" size="sm"
                                class="text-red-600 cursor-pointer ms-1">
                                {{ __("Delete") }}
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="delete-user-{{ $u->id }}" class="md:w-96">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">{{ __("Delete user") }}</flux:heading>
                                    <flux:text class="mt-2">{{ __("Are you sure you want to delete this user? This action cannot be
                                            undone.") }}</flux:text>
                                </div>

                                <div class="flex gap-2">
                                    <flux:spacer />

                                    <flux:modal.close>
                                        <flux:button variant="ghost" type="button" class="cursor-pointer">
                                            {{ __("Cancel") }}
                                        </flux:button>
                                    </flux:modal.close>

                                    <flux:modal.close>
                                        <flux:button wire:click="delete({{ $u->id }})" type="button" variant="danger"
                                            color="green" class="cursor-pointer">
                                            {{ __("Confirm") }}
                                        </flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $users->links() }}</div>
</section>