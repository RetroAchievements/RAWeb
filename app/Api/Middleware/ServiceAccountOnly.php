<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceAccountOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $allowedUserIds = $this->getAllowedUserIds();

        if (!in_array($user->id, $allowedUserIds, true)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return $next($request);
    }

    /**
     * Get the list of allowed user IDs from configuration.
     */
    private function getAllowedUserIds(): array
    {
        $configValue = config('internal-api.allowed_user_ids', '');

        if (empty($configValue)) {
            return [];
        }

        // Convert the comma-separated string from config to an array of integers.
        return array_map(
            fn ($id) => (int) trim($id),
            explode(',', $configValue)
        );
    }
}
