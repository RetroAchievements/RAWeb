<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBanned
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var ?User $user */
        $user = Auth::user();

        if ($user && $user->isBanned()) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
