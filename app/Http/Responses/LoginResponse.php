<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte est désactivé. Contactez l’administrateur (support@fecofa.cd).']);
        }

        if (method_exists($user, 'hasRole') && $user->hasRole(['Owner', 'Administrator'])) {
            $redirect = '/admin/dashboard';
        } else {
            $redirect = '/dashboard';
        }

        $user->forceFill(['last_login_at' => now()])->save();

        Log::info('Redirect after login', ['user' => Auth::id()]);

        return $request->wantsJson()
                    ? response()->json(['redirect' => $redirect])
                    : redirect()->intended($redirect);
    }
}
