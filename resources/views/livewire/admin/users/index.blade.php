<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new class extends Component {
    //
    use WithPagination;

    public string $search = '';
    public string $name = '';
    public string $email = '';  
    public array $roles = [];

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

    public function delete(int $id)
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        User::findOrFail($id)->delete();

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

                    <flux:input label="Name" wire:model="name" type="text" placeholder="Your name"/>
                    <flux:input label="Email" wire:model="email" type="email" placeholder="Your email"/>

                    <flux:select wire:model="role" placeholder="Select role">
                        <flux:select.option>
                            <div class="flex items-center gap-2">
                                <flux:icon.key variant="mini" class="text-zinc-400" /> Administrator
                            </div>
                        </flux:select.option>

                        <flux:select.option>
                            <div class="flex items-center gap-2">
                                <flux:icon.user variant="mini" class="text-zinc-400" /> Member
                            </div>
                        </flux:select.option>

                        <flux:select.option>
                            <div class="flex items-center gap-2">
                                <flux:icon.eye variant="mini" class="text-zinc-400" /> Viewer
                            </div>
                        </flux:select.option>
                    </flux:select>

                    <flux:button type="button" variant="primary" color="green" class="cursor-pointer" wire:navigate>
                        {{ __("Save") }}
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
                    <a class="font-medium text-blue-600 dark:text-blue-500 hover:underline cursor-pointer" href="#" wire:navigate>
                        {{ __("Edit") }}
                    </a>
                    <button class="font-medium text-red-600 dark:text-red-500 hover:underline cursor-pointer" @click.prevent="if (confirm('Confirmer la suppression ?')) $wire.delete({{ $u->id }})">
                        {{ __("Delete") }}
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $users->links() }}</div>
</section>
