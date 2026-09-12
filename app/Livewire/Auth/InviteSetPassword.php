<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class InviteSetPassword extends Component
{
    public string $token;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[Locked]
    public string $broker = 'invites';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request('email', '');
        $this->broker = request()->routeIs('password.reset')
            ? config('fortify.passwords', 'users')
            : 'invites';
    }

    public function save()
    {
        $this->validate([
            'token' => ['required'],
            // 'email'=>['required', 'email', 'regex:/@fecofa\.cd$/i'],
            'password' => ['required', 'confirmed', 'min:8', PasswordRule::defaults()],
        ]);

        $status = Password::broker($this->broker)->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'password_set_at' => now(),
                ])->setRememberToken(Str::random(60));

                if ($this->broker === 'invites' && ! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            session()->flash('status', __($status));

            return redirect()->route('login');
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render()
    {
        return view('livewire.auth.invite-set-password')->title('Définir mon mot de passe')->layout('components.layouts.auth');
    }
}
