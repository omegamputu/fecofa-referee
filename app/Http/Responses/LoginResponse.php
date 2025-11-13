<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
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

        if (method_exists($user, 'hasRole') && $user->hasRole(['Super_Admin', 'Admin'])) {
            $redirect = '/admin/dashboard';
        } else {
            $redirect = '/dashboard';
        }

        return $request->wantsJson()
                    ? response()->json(['redirect' => $redirect])
                    : redirect()->intended($redirect);
    }
}