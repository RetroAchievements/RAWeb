<?php

namespace App\Site\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect(session()->get('intended_url'));
    }
}
