<div>
    <flux:modal name="edit-user-{{ $user->id ?? '0' }}" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">Edit user</flux:heading>

            <flux:input label="Name" wire:model.defer="editName" />
            <flux:input label="Email" wire:model.defer="editEmail" />

            <flux:select wire:model="selectedRole" label="Role">
                @foreach($roles as $r)
                    <flux:select.option value="{{ $r }}">{{ ucfirst($r) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button wire:click="update" variant="primary">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
