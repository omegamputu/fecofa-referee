<?php

namespace App\Livewire\Admin\Modals;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ResendInvitationModal extends Component
{
    public ?User $user = null;
    
    protected $listeners = ['load' => 'load'];

    public function load($id) {
        $this->user = User::findOrFail($id);
    }

    public function resend()
    {
        $this->authorize('manage_users');

        if (! $this->user) {
            return;
        }

        // Protection : Owner et SUPER_ADMIN ne reçoivent pas d’invitations
        if ($this->user->hasRole(['Owner', 'SUPER_ADMIN'])) {
            abort(403, "You cannot resend invitation to this user.");
        }

        // Utilisation du broker "invites"
        Password::broker('invites')->sendResetLink([
            'email' => $this->user->email
        ]);

        // Mise à jour des métriques d’invitation
        $this->user->invited_at = now();
        $this->user->increment('invitation_sent_count');
        $this->user->save();

        // Fermeture du modal
        $this->dispatchBrowserEvent('close-modal', [
            'name' => 'resent-invitation-user-' . $this->user->id
        ]);

        // Rafraîchir le parent
        $this->emitUp('invitationResent');
    }

    public function render()
    {
        return view('livewire.admin.modals.resend-invitation-modal');
    }
}
