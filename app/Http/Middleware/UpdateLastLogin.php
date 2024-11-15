<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateLastLogin
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var ?User $user */
        $user = Auth::user();

        if ($user) {
            $user->LastLogin = Carbon::now();
            $user->save();
        }

        return $next($request);
    }
}
