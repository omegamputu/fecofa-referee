<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

class InviteSetPassword extends Component
{
    public string $token;
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public function mount(string $token) : void
    {
        $this->token = $token;
        $this->email = request('email', '');
    }

    public function save()
    {
        $this->validate([
            'token'=>['required'],
            //'email'=>['required', 'email', 'regex:/@fecofa\.cd$/i'],
            'password'=>['required', 'confirmed', 'min:8', PasswordRule::defaults()],
        ]);

        $status = Password::broker('invites')->reset(
            [
                'email'=>$this->email,
                'password'=>$this->password,
                'password_confirmation'=>$this->password_confirmation,
                'token'=>$this->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'password_set_at' => now(),
                ])->setRememberToken(\Illuminate\Support\Str::random(60));

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
