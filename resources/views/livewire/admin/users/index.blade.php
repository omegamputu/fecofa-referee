<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;

new class extends Component {
    //
    use WithPagination;

    public string $search = '';
    public string $name = '';
    public string $email = '';  
    public string $role = '';
    public array $roles = [];

    // Edit
    public $editUserId;
    public $editName;
    public $editEmail;
    public $selectedRoles = [];

    public function mount(): void
    {
        //
        $this->roles = Role::whereNotIn('name', ['Owner'])->pluck('name')->toArray();
    }
    

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function with()
    {
        return [
            'users' => User::query()
                ->where('name', 'like', '%'.$this->search.'%')
                ->orderBy('created_at', 'desc')
                ->paginate(10)
        ];
    }

    public function toggleActive(int $id)
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $user = User::findOrFail($id);

        if ($user->hasRole('Owner')) {
            # code...
            abort(403, "You can not change status of Owner");
        }

        $user->is_active = ! $user->is_active;

        $user->save();

        session()->flash('message', 'User status updated successfully.');
    }

    public function createUser()
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email','max:255','unique:users,email','regex:/@fecofa\.cd$/i'],
            'role' => 'required|string|in:'.implode(',', $this->roles),
        ]);

        // Create user
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt(str()->random(12)),
        ]);

        // Role assigned

        $user->assignRole($this->role);

        /*
        * INVITATION
        * Alternatively, you can use the sendInvite method
        */

        $this->sendInvite($user);

        $user->forceFill([
            'invited_at' => now(),
        ])->save();

        $user->increment('invitation_sent_count');

        session()->flash('message', 'User created successfully.');

        $this->reset(['name', 'email', 'role']);
    }

    public function sendInvite(User $user): void
    {
        Cache::put("invite:{$user->email}", true, now()->addMinutes(2));
        Password::broker('invites')->sendResetLink(['email' => $user->email]);
    }

    public function editUser(int $id)
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $user = User::findOrFail($id);

        $this->editUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->selectedRoles = $user->roles->pluck('name')->first();
    }

    public function updateUser()
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmail' => ['required','email','max:255','unique:users,email,'.$this->editUserId,'regex:/@fecofa\.cd$/i'],
        ]);

        // Update user
        $user = User::find($this->editUserId);

        $user->name = $this->editName;
        $user->email = $this->editEmail;

        $user->save();

        $user->syncRoles([$this->selectedRoles]);

        Flux::modal("edit-user-{{ $user->id }}")->close();

        session()->flash('message', 'User updated successfully.');
    }

    public function delete(int $id)
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $user = User::findOrFail($id);

        if ($user->hasRole('Owner')) {
            # code...
            abort(403, "You can delete Owner");
        }

        $user->delete();

        Flux::modal("delete-user-{{ $user->id }}")->close();

        session()->flash('message', 'User deleted successfully.');
    }
}; 

?>

<section class="container mx-auto h-full w-full max-w-7xl px-6">
    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <div class="flex items-center justify-between mb-4">
        <div>
            <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">
                {{ __("Search") }}
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="text" id="default-search" wire:model.live="search" class="block w-full p-3 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="{{ __("Search by name") }}" required />
            </div>
        </div>

        <div>
            <flux:modal.trigger name="create-user">
                <flux:button variant="primary" class="cursor-pointer" wire:navigate>
                    {{ __("Add user") }}
                </flux:button>
            </flux:modal.trigger>

            <flux:modal name="create-user" class="md:w-96">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Create user</flux:heading>
                        <flux:text class="mt-2">Decribe personal details.</flux:text>
                    </div>

                    <flux:input label="Name" wire:model.defer="name" type="text" placeholder="Your name" required />
                    <flux:input label="Email" wire:model.defer="email" type="email" placeholder="Your email" required />

                    <flux:select wire:model="role" placeholder="Select role" required>
                        @foreach ($roles as $role)
                        <flux:select.option class="text-zinc-400" value="{{ $role }}">{{ ucfirst($role) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button wire:click="createUser" type="button" variant="primary" color="green" class="w-full cursor-pointer">
                        {{ __("Invite user") }}
                    </flux:button>
                </div>
            </flux:modal>
        </div>
    </div>

    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 border border-neutral-200 dark:border-neutral-700 rounded-xl">
        <thead class="text-xs text-gray-700 uppercase dark:text-gray-400">
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
            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                <th scope="row" class="flex items-center px-6 py-4 text-gray-900 whitespace-nowrap dark:text-white"">
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
                        <flux:button wire:click="editUser({{ $u->id }})" :loading="false" class="xs cursor-pointer">
                            {{ __("Edit") }}
                        </flux:button>
                    </flux:modal.trigger>

                    <flux:modal name="edit-user-{{ $u->id }}" class="md:w-96" wire:key="edit-user-modal-{{ $u->id }}">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Edit user</flux:heading>
                                <flux:text class="mt-2">Update personal details.</flux:text>
                            </div>

                            <flux:input label="Name" wire:model.defer="editName" type="text" placeholder="Your name"/>
                            <flux:input label="Email" wire:model.defer="editEmail" type="email" placeholder="Your email"/>

                            <flux:select wire:model="selectedRoles" placeholder="Select role">
                                @foreach ($roles as $role)
                                <flux:select.option class="text-zinc-400" value="{{ $role }}">{{ ucfirst($role) }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:button wire:click="updateUser" type="button" variant="primary" color="green" class="w-full cursor-pointer">
                                {{ __("Update") }}
                            </flux:button>
                        </div>
                    </flux:modal>

                    

                    @if (! $u->hasRole('Owner'))
                        <flux:modal.trigger name="toggle-active-user-{{ $u->id }}">
                            <flux:button variant="{{ $u->is_active ? 'primary' : 'primary' }}" color="{{ $u->is_active ? 'orange' : 'emerald' }}" :loading="false" class="xs cursor-pointer ms-1">
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
                                    <flux:heading size="lg">{{ $u->is_active ? 'Deactivate' : 'Activate' }} user</flux:heading>
                                    <flux:text class="mt-2">Are you sure you want to {{ $u->is_active ? 'deactivate' : 'activate' }} this user?</flux:text>
                                </div>

                                <flux:button wire:click="toggleActive({{ $u->id }})" type="button" variant="{{ $u->is_active ? 'danger' : 'primary' }}" color="{{ $u->is_active ? 'red' : 'green' }}" class="cursor-pointer">
                                    {{ __("Confirm") }}
                                </flux:button>
                            </div>
                        </flux:modal>

                        <flux:modal.trigger name="delete-user-{{ $u->id }}">
                            <flux:button variant="danger" :loading="false" class="xs text-red-600 cursor-pointer ms-1">
                                {{ __("Delete") }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endif

                    <flux:modal name="delete-user-{{ $u->id }}" class="md:w-96">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Delete user</flux:heading>
                                <flux:text class="mt-2">Are you sure you want to delete this user? This action cannot be undone.</flux:text>
                            </div>

                            <flux:button wire:click="delete({{ $u->id }})" type="button" variant="danger" color="red" class="cursor-pointer">
                                {{ __("Confirm") }}
                            </flux:button>
                        </div>
                    </flux:modal>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $users->links() }}</div>
</section>
