<div>
    @props(['user'])

    <flux:dropdown align="right">
        <flux:dropdown.trigger>
            <flux:button icon="ellipsis-vertical" size="sm" />
        </flux:dropdown.trigger>

        <flux:dropdown.content class="w-48">
            <flux:dropdown.item wire:click="$emit('requestEditUser', {{ $user->id }})" icon="pencil">Edit</flux:dropdown.item>

            <flux:dropdown.item wire:click="$emit('requestResendInvitation', {{ $user->id }})" icon="arrow-path">Resend</flux:dropdown.item>

            @if($user->is_active)
                <flux:dropdown.item wire:click="$emit('requestToggleActiveUser', {{ $user->id }})" icon="pause-circle">Deactivate</flux:dropdown.item>
            @else
                <flux:dropdown.item wire:click="$emit('requestToggleActiveUser', {{ $user->id }})" icon="check-circle">Activate</flux:dropdown.item>
            @endif
            
            <flux:dropdown.item wire:click="$emit('requestDeleteUser', {{ $user->id }})" icon="trash" class="text-red-600">Delete</flux:dropdown.item>
        </flux:dropdown.content>
    </flux:dropdown>
</div>