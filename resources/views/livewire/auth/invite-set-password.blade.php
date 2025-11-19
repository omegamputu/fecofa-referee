<div>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Set your password')" :description="__('Enter your password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form wire:submit="save" class="flex flex-col gap-6">
            {{-- hidden fields --}}
            <input type="hidden" wire:model="token">
            {{-- email en lecture seule pour éviter la confusion (vient du lien) --}}

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </div>
</div>
