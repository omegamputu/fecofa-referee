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
        $this->roles = Role::with('permissions')->whereNotIn('name', ['Owner'])->get()->toArray();
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
                            <th scope="col" class="px-6 py-3">{{ __('Name') }}</th>
                            <th scope="col" class="px-6 py-3 text-left">{{ __('Permissions') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($roles as $role)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $role['name'] }}
                            </td>
                            <td class="px-6 py-4">
                                @if (!empty($role['permissions']))
                                    @foreach ($role['permissions'] as $permission)
                                        <span
                                            class="inline-flex items-center gap-x-0.5 rounded-md bg-indigo-50 px-2 py-1 mb-2
                                                                                                                                                                                         text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10
                                                                                                                                                                                         dark:bg-indigo-900/30 dark:text-indigo-300 dark:ring-indigo-700/30">
                                            {{ $permission['name'] }}
                                        </span>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <flux:button size="xs" variant="ghost"
                                    class="cursor-pointer dark:bg-[#0E1526] dark:text-white hover:dark:bg-[#0080C0]"
                                    :href="route('admin.roles.edit', $role['id'])" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>