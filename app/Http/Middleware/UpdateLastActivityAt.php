<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Platform\Services\UserLastActivityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateLastActivityAt
{
    public function __construct(
        private readonly UserLastActivityService $userActivityService,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if ($user) {
            $this->userActivityService->touch($user);
        }

        return $next($request);
    }
}
