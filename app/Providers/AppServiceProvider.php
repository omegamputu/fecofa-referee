<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use App\Models\League;
use App\Models\User;
use App\Policies\LeaguePolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        app('router')->pushMiddlewareToGroup('web', SetLocale::class);
        app('router')->pushMiddlewareToGroup('web', EnsureUserIsActive::class);

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
                ->line('Vous avez été invité à utiliser l’application de gestion des arbitres de la FECOFA.')
                ->action('Définir mon mot de passe', $url)
                ->line('Ce lien expirera dans '.config('auth.passwords.invites.expire').' minutes.')
                ->line('Si vous n’êtes pas à l’origine de cette invitation, ignorez cet email.');
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(League::class, LeaguePolicy::class);
    }
}
