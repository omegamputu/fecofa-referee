<?php

namespace App\Livewire\Admin\Modals;

use App\Models\User;
use Livewire\Component;

class ToggleActiveUserModal extends Component
{
    public ?User $user = null;
    public bool $isActiveBefore = false;

    protected $listeners = ['load' => 'load'];

    /**
     * Chargé lorsque le parent émet : $emitTo('admin.modals.toggle-active-user-modal', 'load', $id)
     */
    public function load($id)
    {
        $this->user = User::findOrFail($id);
        $this->isActiveBefore = $this->user->is_active;
    }

    /**
     * Confirme l’activation/désactivation
     */
    public function toggle()
    {
        if (! $this->user) {
            return;
        }

        // Bloquer Owner ou Super Admin
        if ($this->user->hasRole(['Owner', 'SUPER_ADMIN'])) {
            abort(403, "You cannot change status of this user.");
        }

        $this->user->is_active = ! $this->user->is_active;
        $this->user->save();

        // Ferme le modal
        $this->dispatchBrowserEvent('close-modal', [
            'name' => 'toggle-active-user-' . $this->user->id
        ]);

        // Demande au parent de rafraîchir
        $this->emitUp('userToggled');
    }

    public function render()
    {
        return view('livewire.admin.modals.toggle-active-user-modal');
    }
}
