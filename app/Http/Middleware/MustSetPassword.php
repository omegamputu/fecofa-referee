<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MustSetPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check())
        {
            $user = Auth::user();

            // Exemption pour le SuperAdmin
            if (method_exists($user, 'hasRole') && $user->hasRole('Super_Admin')) {
                return $next($request);
            }

            // Bloauer tous les utilisateurs sans mot de passe défini
            if (is_null($user->password_set_at))
            {
                $user->logout();

                return redirect()->route('login')
                    ->withErrors(['email' => "Vous devez définir un mot de passe avant de continuer. Veuillez utiliser le lien de réinitialisation du mot de passe envoyé à votre adresse e-mail."]);
            }
        }

        return $next($request);
    }
}
