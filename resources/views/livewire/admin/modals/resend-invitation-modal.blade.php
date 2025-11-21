<div>
    <flux:modal name="resent-invitation-user-{{ $u->id }}" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Resend Invitation</flux:heading>
                <flux:text class="mt-2">Are you sure you want to resend the invitation to this user?</flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost" type="button" class="cursor-pointer">
                        {{ __("Cancel") }}
                    </flux:button>
                </flux:modal.close>

                <flux:button wire:click="resend" type="button" variant="primary" color="green" class="cursor-pointer">
                    {{ __("Confirm") }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
