<?php

namespace App\Livewire\Admin\Modals;

use App\Models\User;
use Livewire\Component;

class DeleteUserModal extends Component
{
    public ?User $user = null;
    
    protected $listeners = ['load' => 'load'];

    public function load($id) {
        $this->user = User::findOrFail($id);
    }

    public function confirmDelete() {
        $this->user->delete();
        $this->dispatchBrowserEvent('close-modal', ['name'=>'delete-user-'.$this->user->id]);
        $this->emitUp('userDeleted');
    }
    public function render()
    {
        return view('livewire.admin.modals.delete-user-modal');
    }
}
