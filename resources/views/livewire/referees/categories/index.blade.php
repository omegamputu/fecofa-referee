<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Referees\RefereeCategory;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    // Create
    public string $name = '';
    public ?string $slug = null;
    public ?string $description = null;

    // Edit
    public ?int $editCategoryId = null;
    public string $editName = '';
    public ?string $editSlug = null;
    public ?string $editDescription = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'categories' => RefereeCategory::query()
                ->when($this->search, function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                })
                ->orderBy('id', 'asc')
                ->paginate($this->perPage),
        ];
    }

    /* ---------- Validation ---------- */

    protected function createRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:referee_categories,name'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:referee_categories,slug'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function updateRules(): array
    {
        return [
            'editName' => ['required', 'string', 'max:255', 'unique:referee_categories,name,' . $this->editCategoryId],
            'editSlug' => ['nullable', 'string', 'max:50', 'unique:referee_categories,slug,' . $this->editCategoryId],
            'editDescription' => ['nullable', 'string', 'max:255'],
        ];
    }



    public function createCategory(): void
    {
        $this->authorize('manage_referee_categories', RefereeCategory::class); // si tu ajoutes une policy plus tard

        $data = $this->validate($this->createRules());

        RefereeCategory::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        session()->flash('status', __('Category created successfully.'));

        $this->resetPage();

        $this->redirectRoute('referees.categories.index');
    }

    public function editCategory(int $id): void
    {
        $this->authorize('manage_referee_categories', RefereeCategory::class);

        $category = RefereeCategory::findOrFail($id);

        $this->editCategoryId = $category->id;
        $this->editName = $category->name;
        $this->editDescription = $category->description;
    }

    public function updateCategory(): void
    {
        $this->authorize('manage', RefereeCategory::class);

        $data = $this->validate($this->updateRules());

        $category = RefereeCategory::findOrFail($this->editCategoryId);

        $category->update([
            'name' => $data['editName'],
            'slug' => Str::slug($data['editName']),
            'description' => $data['editDescription'] ?? null,
        ]);

        session()->flash('status', __('Category updated successfully.'));

        $this->redirectRoute('referees.categories.index');
    }

    public function deleteCategory(int $id): void
    {
        $this->authorize('manage', RefereeCategory::class);

        $category = RefereeCategory::findOrFail($id);

        $category->delete();

        session()->flash('status', __('Category deleted successfully.'));

        $this->resetPage();

        $this->redirectRoute('admin.referees.categories.index');
    }
}
?>

<section class="container mx-auto w-full max-w-7xl bg-white dark:bg-neutral-900 dark:rounded-xl py-6 px-6 rounded-3xl">
    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <h1 class="text-2xl font-semibold dark:text-neutral-400 mb-4">{{ __("Referee Categories") }}</h1>

    {{-- Search + bouton créer --}}
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

                <flux:input class="w-full" icon="magnifying-glass" placeholder="{{ __('Search by name') }}"
                    wire:model.live.debounce.400ms="search" />
            </div>
        </div>

        <flux:modal.trigger name="create-category">
            <flux:button variant="primary" color="green" class="cursor-pointer">{{ __("Add category") }}</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Tableau --}}
    <table
        class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 bg-white dark:bg-[#0E1526] dark:border-neutral-700 py-6 px-6 rounded-xl">
        <thead class="text-xs text-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">{{ __("ID") }}</th>
                <th scope="col" class="px-6 py-3">{{ __("Name") }}</th>
                <th scope="col" class="px-6 py-3">{{ __("Slug") }}</th>
                <th scope="col" class="px-6 py-3">{{ __("Description") }}</th>
                <th scope="col" class="px-6 py-3">{{ __("Actions") }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
                <tr>
                    <td class="px-6 py-4">{{ $category->id }}</td>
                    <td class="px-6 py-4">{{ $category->name }}</td>
                    <td class="px-6 py-4">{{ $category->slug }}</td>
                    <td class="px-6 py-4">{{ $category->description }}</td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2">
                            {{-- Edit --}}
                            <flux:modal.trigger name="edit-category-{{ $category->id }}">
                                <flux:button wire:click="editCategory({{ $category->id }})" size="sm"
                                    class="cursor-pointer">
                                    {{ __("Edit") }}
                                </flux:button>
                            </flux:modal.trigger>

                            {{-- Delete --}}
                            <flux:modal.trigger name="delete-category-{{ $category->id }}">
                                <flux:button variant="danger" size="sm" class="cursor-pointer">{{ __("Delete") }}
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    {{-- Modal Create --}}
    <flux:modal name="create-category" class="md:w-96" variant="flyout">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Create category") }}</flux:heading>

            <flux:input label="{{ __('Name') }}" wire:model.defer="name" />
            <flux:textarea label="{{ __('Description') }}" wire:model.defer="description" />

            <flux:modal.close>
                <flux:button wire:click="createCategory" class="w-full" variant="primary">
                    {{ __("Save") }}
                </flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>

    {{-- Modal Edit --}}
    @foreach($categories as $category)
        <flux:modal name="edit-category-{{ $category->id }}" class="md:w-96" wire:key="edit-category-{{ $category->id }}"
            variant="flyout">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __("Edit category") }}</flux:heading>

                <flux:input label="{{ __('Name') }}" wire:model.defer="editName" />
                <flux:textarea label="{{ __('Description') }}" wire:model.defer="editDescription" />

                <flux:modal.close>
                    <flux:button wire:click="updateCategory" class="w-full" variant="primary">
                        {{ __("Update") }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </flux:modal>

        <flux:modal name="delete-category-{{ $category->id }}" class="md:w-96">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __("Delete category") }}</flux:heading>
                <flux:text>{{ __("Are you sure you want to delete this category?") }}</flux:text>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __("Cancel") }}</flux:button>
                    </flux:modal.close>

                    <flux:modal.close>
                        <flux:button wire:click="deleteCategory({{ $category->id }})" variant="danger">
                            {{ __("Confirm") }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endforeach
</section>