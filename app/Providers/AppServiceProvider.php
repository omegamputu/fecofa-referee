<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app('router')->pushMiddlewareToGroup('web', \App\Http\Middleware\SetLocale::class);
        
        // Le Super-Admin passe avant tous les checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Owner') ? true : null;
        });

        ResetPassword::toMailUsing(function ($notifiable, $token) {
        $url = url(route('invite.accept', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Invitation')
            ->greeting('Bonjour '.$notifiable->name)
            ->line("Vous avez été invité à utiliser l’application de gestion des arbitres de la FECOFA.")
            ->action('Définir mon mot de passe', $url)
            ->line('Ce lien expirera dans '.config('auth.passwords.invites.expire').' minutes.')
            ->line("Si vous n’êtes pas à l’origine de cette invitation, ignorez cet email.");
        });

        Gate::policy(User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\League::class, \App\Policies\LeaguePolicy::class);
    }
}
