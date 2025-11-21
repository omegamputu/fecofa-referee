<div>
    <flux:modal name="delete-user-{{ $u->id }}" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete user</flux:heading>
                <flux:text class="mt-2">Are you sure you want to delete this user? This action cannot be undone.</flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost" type="button" class="cursor-pointer">
                        {{ __("Cancel") }}
                    </flux:button>
                </flux:modal.close>

                <flux:button wire:click="confirmDelete" type="button" variant="danger" color="green" class="cursor-pointer">
                    {{ __("Confirm") }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
