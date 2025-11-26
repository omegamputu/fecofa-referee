<div>
    @if($user)
    <flux:modal name="toggle-active-user-{{ $user->id }}" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $isActiveBefore ? __('Deactivate user') : __('Activate user') }}
                </flux:heading>

                <flux:text class="mt-2">
                    {{ $isActiveBefore
                        ? __('Are you sure you want to deactivate this user?')
                        : __('Are you sure you want to activate this user?')
                    }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost" type="button" class="cursor-pointer">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button
                    wire:click="toggle"
                    type="button"
                    variant="primary"
                    color="{{ $isActiveBefore ? 'orange' : 'emerald' }}"
                    class="cursor-pointer"
                >
                    {{ __('Confirm') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @endif
</div>
