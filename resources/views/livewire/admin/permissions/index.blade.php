<?php
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new class extends Component {

    public string $search = '';
    public array $roles = [];
    public array $permissions = [];

    public ?int $editRoleId = null;
    public string $editRoleName = '';
    public array $editRolePermissions = [];

    public function mount(): void
    {
        $this->roles = Role::with('permissions')->get()->toArray();
        //$this->permissions = \Spatie\Permission\Models\Permission::all()->toArray();
    }

    public function editRole(int $id): void
    {
        $this->authorize('manage_roles', Role::class);

        $role = Role::findOrFail($id);

        $this->editRoleId = $role->id;
        $this->editRoleName = $role->name;
        $this->editRolePermissions = $role->permissions->pluck('name')->toArray();
    }

    public function updateRole(): void
    {
        $this->authorize('manage_roles', Role::class);

        $role = Role::findOrFail($this->editRoleId);

        $role->update([
            'name' => $this->editRoleName,
        ]);

        session()->flash('status', __('Role updated successfully.'));
    }
}

?>

<div>
    <div class="container mx-auto h-full w-full max-w-7xl px-6">
        <section class="bg-white dark:bg-[#0E1526] dark:border dark:border-neutral-600 rounded-xl p-4">
            <h2 class="text-base font-semibold dark:text-neutral-500 mb-3">
                {{ __('All roles') }}
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b dark:border-neutral-600 text-neutral-500 text-xs">
                        <tr>
                            <th class="py-2 text-left">{{ __('Name') }}</th>
                            <th class="py-2 text-left">{{ __('Permissions') }}</th>
                            <th class="py-2 text-left">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($roles as $role)
                        <tr>
                            <td class="py-2">
                                {{ $role['name'] }}
                            </td>
                            <td class="py-2">
                                @if (!empty($role['permissions']))
                                    @foreach ($role['permissions'] as $permission)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 mr-1 mb-1 text-xs font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300">
                                            {{ $permission['name'] }}
                                        </span>
                                    @endforeach
                                @endif
                            </td>
                            <td class="py-2">
                                <flux:modal.trigger name="edit-role-{{ $role['id'] }}">
                                    <flux:button wire:click="editRole({{ $role['id'] }})" :loading="false" size="xs"
                                        variant="ghost"
                                        class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]">
                                        {{ __("Edit") }}
                                    </flux:button>
                                </flux:modal.trigger>

                                <flux:modal name="edit-role-{{ $role['id'] }}" :close-on-outside-click="false"
                                    :close-on-escape="false" size="lg" variant="flyout">
                                    <div class="mb-4">
                                        <flux:heading size="lg">{{ __("Edit role") }}</flux:heading>
                                        <flux:text class="mt-2">{{ __("Update role & permissions.") }}</flux:text>
                                    </div>

                                    <flux:input label="{{ __('Name') }}" wire:model.defer="editRoleName" type="text" />

                                    <div class="flex gap-2 mt-4 flex-wrap">
                                        <flux:modal.close>
                                            <flux:button variant="ghost" type="button" class="cursor-pointer">
                                                {{ __("Cancel") }}
                                            </flux:button>
                                        </flux:modal.close>
                                        <flux:modal.close>
                                            <flux:button wire:click="updateRole" type="button" variant="primary"
                                                color="green" class="cursor-pointer"> {{ __("Update") }} </flux:button>
                                        </flux:modal.close>
                                    </div>
                                </flux:modal>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>