<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UsersIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public array $roles = [];

    public function mount()
    {
        $this->roles = Role::whereNotIn('name', 'Owner')->pluck('name')->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::query()
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'Owner');
                })
                ->where('name', 'like', '%'.$this->search.'%')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

        return view('livewire.admin.users-index', compact('users'))->layout('components.layouts.app');
    }

    // Handlers called by dropdown (via $emit events)
    public function requestEditUser($id)
    {
        // tell EditUserModal to load data, then open modal in client
        $this->emitTo('admin.modals.edit-user-modal', 'load', $id);
        $this->dispatchBrowserEvent('open-modal', ['name' => "edit-user-{$id}"]);
    }

    public function requestDeleteUser($id)
    {
        $this->emitTo('admin.modals.delete-user-modal', 'load', $id);
        $this->dispatchBrowserEvent('open-modal', ['name' => "delete-user-{$id}"]);
    }

    public function requestToggleActiveUser($id)
    {
        $this->emitTo('admin.modals.toggle-active-user-modal', 'load', $id);
        $this->dispatchBrowserEvent('open-modal', ['name' => "toggle-active-user-{$id}"]);
    }

    public function requestResendInvitation($id)
    {
        $this->emitTo('admin.modals.resend-invitation-modal', 'load', $id);
        $this->dispatchBrowserEvent('open-modal', ['name' => "resent-invitation-user-{$id}"]);
    }
}
