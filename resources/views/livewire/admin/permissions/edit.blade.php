<?php 

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {

    public Role $role;
    public array $rolePermissions = [];
    public array $allPermissions = [];
    public string $newPermission = '';
    public string $createPermissionName = '';

    public function mount(Role $role)
    {
        $this->role = $role;

        // Permissions déjà liées
        $this->rolePermissions = $role->permissions->pluck('name')->toArray();

        // Permissions disponibles
        $this->allPermissions = Permission::pluck('name')->toArray();
    }

    public function createPermission()
    {
        $name = trim($this->createPermissionName);

        if (!$name || Permission::where('name', $name)->exists()) {
            session()->flash('error', __('Permission already exists or invalid name.'));
            return;
        }
        // Créer la permission
        $permission = Permission::create(['name' => $name]);
        // On met à jour la liste des permissions disponibles
        $this->allPermissions[] = $permission->name;

        $this->createPermissionName = '';

        session()->flash('status', __('Permission created successfully.'));
    }

    public function addPermission()
    {
        if (!$this->newPermission || in_array($this->newPermission, $this->rolePermissions)) {
            return;
        }

        $this->role->givePermissionTo($this->newPermission);
        $this->rolePermissions[] = $this->newPermission;
        $this->newPermission = '';

        session()->flash('status', __('Permission added.'));
    }

    public function removePermission($permission)
    {
        $this->role->revokePermissionTo($permission);
        $this->rolePermissions = array_values(array_diff($this->rolePermissions, [$permission]));

        session()->flash('status', __('Permission removed.'));
    }

    public function updateRole()
    {
        $this->role->save();

        session()->flash('status', __('Role updated successfully.'));

        $this->redirectRoute('admin.roles.index');
    }
};
?>

<div>
    <section class="container mx-auto max-w-5xl py-8">

        <div class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
            <h1 class="text-2xl font-semibold mb-6 dark:text-neutral-200">
                {{ __('Edit Role') }} — <span class="text-indigo-500">{{ $role->name }}</span>
            </h1>

            <x-auth-session-status :status="session('status')" class="mb-4" />

            {{-- Nom du rôle --}}
            <div class="flex items-center gap-3 mb-6">
                <flux:input label="{{ __('Permission name') }}" wire:model.defer="createPermissionName"
                    placeholder="manage_posts" class="min-w-[200px]" required />

                <flux:button variant="primary" wire:click="createPermission" class="cursor-pointer mt-6">
                    {{ __('Create Permission') }}
                </flux:button>
            </div>

            {{-- Permissions déjà assignées --}}
            <h2 class="text-lg font-semibold mb-2 dark:text-neutral-300">
                {{ __('Permissions assigned') }}
            </h2>

            <div class="flex flex-wrap gap-2 mb-6">

                @forelse ($rolePermissions as $perm)
                    <span
                        class="inline-flex items-center gap-x-0.5 rounded-md bg-indigo-50 px-2 py-1
                                                                                                                                                                                     text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10
                                                                                                                                                                                     dark:bg-indigo-900/30 dark:text-indigo-300 dark:ring-indigo-700/30">

                        {{ $perm }}

                        <button wire:click="removePermission('{{ $perm }}')" type="button"
                            class="group relative -mr-1 h-3.5 w-3.5 rounded-sm hover:bg-indigo-600/20 dark:hover:bg-indigo-800/40 cursor-pointer">
                            <span class="sr-only">Supprimer</span>

                            <svg viewBox="0 0 14 14"
                                class="h-3.5 w-3.5 stroke-indigo-700/50 group-hover:stroke-indigo-700/75">
                                <path d="M4 4l6 6m0-6l-6 6" />
                            </svg>
                        </button>

                    </span>
                @empty
                    <p class="text-sm text-neutral-500">{{ __('No permissions assigned') }}</p>
                @endforelse

            </div>

            {{-- Ajouter une permission --}}
            <h2 class="text-lg font-semibold mb-2 dark:text-neutral-300">
                {{ __('Add permission') }}
            </h2>

            <div class="flex gap-2 items-center">
                <flux:select wire:model="newPermission" searchable class="min-w-[200px]"
                    placeholder="{{ __('Select permission') }}">
                    @foreach ($allPermissions as $perm)
                        <flux:select.option value="{{ $perm }}">
                            {{ $perm }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button variant="primary" wire:click="addPermission" class="cursor-pointer">
                    {{ __('Add') }}
                </flux:button>
            </div>

            <flux:separator class="my-6" />

            <div class="flex gap-4">
                <flux:button variant="ghost"
                    class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]"
                    :href="route('admin.roles.index')" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>

                {{-- Sauvegarder --}}
                <flux:button variant="primary" color="green" wire:click="updateRole" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>
        </div>
    </section>
</div>