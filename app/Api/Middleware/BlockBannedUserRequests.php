<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Actions\FindUserByIdentifierAction;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class BlockBannedUserRequests
{
    private const MAINTAINER_METHODS = [
        'API_GetTicketData',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $identifier = $request->input('u');

        if (!is_string($identifier) || $identifier === '') {
            return $next($request);
        }

        $target = (new FindUserByIdentifierAction())->execute($identifier);

        if (!$target?->isBanned()) {
            return $next($request);
        }

        if ($this->isMaintainer($request)) {
            return $next($request);
        }

        return response()->json([], 404);
    }

    private function isMaintainer(Request $request): bool
    {
        if (!in_array($request->route('method'), self::MAINTAINER_METHODS, true)) {
            return false;
        }

        /** @var ?User $caller */
        $caller = $request->user('api-token');

        return $caller?->hasAnyRole([Role::DEVELOPER, Role::MODERATOR, Role::ADMINISTRATOR]) ?? false;
    }
}
