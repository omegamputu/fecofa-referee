<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['email' => "Votre compte est désactivé. Contactez l’administrateur (support@fecofa.cd)."]);
        }

        if (method_exists($user, 'hasRole') && $user->hasRole(['Owner', 'Administrator'])) {
            $redirect = '/admin/dashboard';
        } else {
            $redirect = '/dashboard';
        }

        Log::info('Redirect after login', ['user' => Auth::id()]);

        return $request->wantsJson()
                    ? response()->json(['redirect' => $redirect])
                    : redirect()->intended($redirect);
    }
}