<?php

namespace App\Livewire\Admin\Modals;
use App\Models\User;
use Spatie\Permission\Models\Role;

use Livewire\Component;

class EditUserModal extends Component
{
    public ?User $user = null;
    public $editName;
    public $editEmail;
    public $selectedRole;

    protected $listeners = ['load' => 'load'];

    public function load($id)
    {
        $this->user = User::findOrFail($id);
        $this->editName = $this->user->name;
        $this->editEmail = $this->user->email;
        $this->selectedRole = $this->user->roles->pluck('name')->first();
    }

    public function update()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|max:255|unique:users,email,'.$this->user->id,
        ]);

        $this->user->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
        ]);

        $this->user->syncRoles([$this->selectedRole]);

        // close modal (Flux listens to open-modal / close-modal events)
        $this->dispatchBrowserEvent('close-modal', ['name' => 'edit-user-'.$this->user->id]);

        // notify parent to refresh
        $this->emitUp('userUpdated');
    }

    public function render()
    {
        $roles = Role::whereNotIn('name', ['SUPER_ADMIN','Owner'])->pluck('name');
        return view('livewire.admin.modals.edit-user-modal', compact('roles'));
    }
}
